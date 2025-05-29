import React, { useState, useEffect, useCallback } from 'react';
import axios from 'axios';

function CleanerSchedule({ authToken }) {
  const [mySchedules, setMySchedules] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [message, setMessage] = useState(null);

  const fetchMySchedules = useCallback(async () => {
    setLoading(true);
    setError(null);
    if (!authToken) {
      setError("Tokeni i autorizimit nuk është i disponueshëm.");
      setLoading(false);
      return;
    }
    try {
      const res = await axios.get('http://localhost:8000/api/cleaner/schedules/my', {
        headers: { Authorization: `Bearer ${authToken}` },
      });
      setMySchedules(res.data);
    } catch (err) {
      setError(
        "Gabim gjatë ngarkimit të orareve tuaja: " +
        (err.response?.data?.message || err.message || "Një gabim i panjohur ndodhi.")
      );
    } finally {
      setLoading(false);
    }
  }, [authToken]);

  useEffect(() => {
    fetchMySchedules();
  }, [fetchMySchedules]);

  const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    try {
      const date = new Date(dateString);
      return date.toLocaleDateString('sq-AL', { year: 'numeric', month: '2-digit', day: '2-digit' });
    } catch (e) {
      return 'N/A';
    }
  };

  const formatTime = (timeString) => {
    if (!timeString) return 'N/A';
    try {
      if (timeString.includes('T') && timeString.includes(':')) {
        const timePart = timeString.split('T')[1]; 
        return timePart.substring(0, 5); 
      } else if (timeString.includes(':')) {
        return timeString.substring(0, 5); 
      }
      return 'N/A'; 
    } catch (e) {
      return 'N/A';
    }
  };

  const handleToggleStatus = async (scheduleId, currentStatus) => {
    let newStatus = currentStatus === 'Completed' ? 'Canceled' : 'Completed';
    setError(null);
    setMessage(null);
    try {
      const response = await axios.put(
        `http://localhost:8000/api/cleaner/schedules/${scheduleId}/status`,
        { status: newStatus },
        {
          headers: {
            'Content-Type': 'application/json',
            Authorization: `Bearer ${authToken}`,
          },
        }
      );
      setMySchedules((prevSchedules) =>
        prevSchedules.map((s) => (s.id === scheduleId ? { ...s, status: newStatus } : s))
      );
      setMessage(response.data.message || `Statusi u ndryshua në "${newStatus}".`);
    } catch (err) {
      setError(
        "Gabim në përditësimin e statusit: " +
        (err.response?.data?.message || err.message || "Një gabim i panjohur ndodhi.")
      );
    }
  };

  if (loading) return <p>Duke ngarkuar oraret...</p>;

  return (
    <div className="container mt-4">
      <h4 className="mb-3">Oraret e Mia të Planifikuara</h4>
      {error && <div className="alert alert-danger mb-3">{error}</div>}
      {message && <div className="alert alert-success mb-3">{message}</div>}

      <div className="table-responsive">
        <table className="table table-bordered table-striped table-hover">
          <thead className="table-dark">
            <tr>
              <th>Data</th>
              <th>Fillimi i Turnit</th>
              <th>Fundi i Turnit</th>
              <th>Statusi</th>
              <th>Veprimet</th>
            </tr>
          </thead>
          <tbody>
            {mySchedules.length === 0 ? (
              <tr>
                <td colSpan={5} className="text-center">
                  Nuk ka orare të planifikuara për ju.
                </td>
              </tr>
            ) : (
              mySchedules.map((schedule) => (
                <tr key={schedule.id}>
                  <td>{formatDate(schedule.work_date)}</td>
                  <td>{formatTime(schedule.shift_start)}</td>
                  <td>{formatTime(schedule.shift_end)}</td>
                  <td>
                    <span
                      className={`badge ${
                        schedule.status === 'Planned'
                          ? 'bg-info text-dark'
                          : schedule.status === 'Completed'
                          ? 'bg-success'
                          : 'bg-danger'
                      }`}
                    >
                      {schedule.status || 'N/A'}
                    </span>
                  </td>
                  <td>
                    <button
                      className={`btn btn-sm ${
                        schedule.status === 'Completed' ? 'btn-warning' : 'btn-primary'
                      }`}
                      onClick={() => handleToggleStatus(schedule.id, schedule.status)}
                      disabled={schedule.status === 'Canceled'} // Nuk mund të ndryshosh statusin nëse është anuluar
                    >
                      {schedule.status === 'Completed' ? 'Anulo Statusin' : 'Përfundo'}
                    </button>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}

export default CleanerSchedule;