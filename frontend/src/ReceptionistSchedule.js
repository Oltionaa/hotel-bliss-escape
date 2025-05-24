import React, { useState, useEffect, useCallback } from 'react';

function ReceptionistSchedule({ authToken }) {
  const [mySchedules, setMySchedules] = useState([]);
  const [allSchedules, setAllSchedules] = useState([]);
  const [loadingMy, setLoadingMy] = useState(true);
  const [loadingAll, setLoadingAll] = useState(true);
  const [errorMy, setErrorMy] = useState(null);
  const [errorAll, setErrorAll] = useState(null);
  const [userId, setUserId] = useState(null);

  const fetchSchedules = useCallback(async () => {
    setLoadingMy(true);
    setLoadingAll(true);
    setErrorMy(null);
    setErrorAll(null);

    if (!authToken) {
      setErrorMy("Tokeni i autorizimit nuk është i disponueshëm.");
      setErrorAll("Tokeni i autorizimit nuk është i disponueshëm.");
      setLoadingMy(false);
      setLoadingAll(false);
      return;
    }

    // Fetch My Schedules
    try {
      const myRes = await fetch('http://localhost:8000/api/receptionist/schedules/my', {
        headers: { Authorization: `Bearer ${authToken}` },
      });
      if (!myRes.ok) {
        const errorData = await myRes.json().catch(() => ({ message: `Gabim serveri: Status ${myRes.status}` }));
        throw new Error(errorData.message || `Gabim gjatë ngarkimit të orareve tuaja: Status ${myRes.status}`);
      }
      const myData = await myRes.json();
      console.log("Orarët e mi të ngarkuara:", myData);
      setMySchedules(myData);
    } catch (err) {
      console.error("Gabim gjatë ngarkimit të orareve të mia:", err.message);
      setErrorMy(err.message);
    } finally {
      setLoadingMy(false);
    }

    // Fetch All Schedules
    try {
      const allRes = await fetch('http://localhost:8000/api/receptionist/schedules/all', {
        headers: { Authorization: `Bearer ${authToken}` },
      });
      if (!allRes.ok) {
        const errorData = await allRes.json().catch(() => ({ message: `Gabim serveri: Status ${allRes.status}` }));
        throw new Error(errorData.message || `Gabim gjatë ngarkimit të të gjitha orareve: Status ${allRes.status}`);
      }
      const allData = await allRes.json();
      console.log("Të gjitha oraret e ngarkuara:", allData);
      setAllSchedules(allData);
    } catch (err) {
      console.error("Gabim gjatë ngarkimit të të gjitha orareve:", err.message);
      setErrorAll(err.message);
    } finally {
      setLoadingAll(false);
    }
  }, [authToken, setMySchedules, setAllSchedules, setErrorMy, setErrorAll, setLoadingMy, setLoadingAll]);

  useEffect(() => {
    const storedUserId = localStorage.getItem('userId');
    if (storedUserId) {
      setUserId(parseInt(storedUserId, 10));
    }
    fetchSchedules();
  }, [fetchSchedules]);

  const handleToggleStatus = async (scheduleId, currentStatus) => {
    let newStatus;
    if (currentStatus === 'Completed') {
      newStatus = 'Canceled';
    } else {
      newStatus = 'Completed';
    }

    setErrorMy(null);
    setErrorAll(null);
    try {
      const response = await fetch(`http://localhost:8000/api/receptionist/schedules/${scheduleId}/status`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${authToken}`,
        },
        body: JSON.stringify({ status: newStatus }),
      });

      if (!response.ok) {
        const errorData = await response.json();
        if (response.status === 403) {
            throw new Error(errorData.message || 'Nuk keni të drejta për të përditësuar këtë orar.');
        }
        throw new Error(errorData.message || 'Gabim gjatë përditësimit të statusit.');
      }

      // Përditëso gjendjet e orareve (mySchedules dhe allSchedules)
      setMySchedules(prevSchedules =>
        prevSchedules.map(s => (s.id === scheduleId ? { ...s, status: newStatus } : s))
      );
      setAllSchedules(prevSchedules =>
        prevSchedules.map(s => (s.id === scheduleId ? { ...s, status: newStatus } : s))
      );

    } catch (err) {
      console.error("Gabim në përditësimin e statusit:", err.message);
      setErrorMy(err.message);
      setErrorAll(err.message);
    }
  };

  if (loadingMy || loadingAll) return <p>Duke ngarkuar oraret...</p>;
  if (errorMy) return <div className="alert alert-danger mt-3">Gabim gjatë ngarkimit të orareve tuaja: {errorMy}</div>;
  if (errorAll) return <div className="alert alert-danger mt-3">Gabim gjatë ngarkimit të të gjitha orareve: {errorAll}</div>;

  return (
    <div className="container mt-4">
      <h2 className="mb-3">Orari yt</h2>
      <div className="table-responsive">
        <table className="table table-bordered table-striped table-hover">
          <thead>
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
              <tr><td colSpan={5}>Nuk ka orare të planifikuara.</td></tr>
            ) : (
              mySchedules.map(schedule => (
                <tr key={String(schedule.id)}>
                  <td>{String(schedule.work_date || 'N/A')}</td>
                  <td>{schedule.shift_start ? String(schedule.shift_start).substring(0, 5) : 'N/A'}</td>
                  <td>{schedule.shift_end ? String(schedule.shift_end).substring(0, 5) : 'N/A'}</td>
                  <td>
                    <span className={`badge ${schedule.status === 'Planned' ? 'bg-info text-dark' : schedule.status === 'Completed' ? 'bg-success' : 'bg-danger'}`}>
                      {String(schedule.status || 'N/A')}
                    </span>
                  </td>
                  <td>
                    <button
                      className="btn btn-sm btn-primary"
                      onClick={() => handleToggleStatus(schedule.id, schedule.status)}
                    >
                      Ndrysho Statusin
                    </button>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      <h2 className="mt-5 mb-3">Oraret e të gjithë recepsionistëve</h2>
      <div className="table-responsive">
        <table className="table table-bordered table-striped table-hover">
          <thead>
            <tr>
              <th>Recepsionisti</th>
              <th>Data</th>
              <th>Fillimi i Turnit</th>
              <th>Fundi i Turnit</th>
              <th>Statusi</th>
            </tr>
          </thead>
          <tbody>
            {allSchedules.length === 0 ? (
              <tr><td colSpan={5}>Nuk ka orare.</td></tr>
            ) : (
              allSchedules.map(schedule => (
                <tr key={String(schedule.id)}>
                  <td>{String(schedule.receptionist?.name || 'N/A')}</td>
                  <td>{String(schedule.work_date || 'N/A')}</td>
                  <td>{schedule.shift_start ? String(schedule.shift_start).substring(0, 5) : 'N/A'}</td>
                  <td>{schedule.shift_end ? String(schedule.shift_end).substring(0, 5) : 'N/A'}</td>
                  <td>
                    <span className={`badge ${schedule.status === 'Planned' ? 'bg-info text-dark' : schedule.status === 'Completed' ? 'bg-success' : 'bg-danger'}`}>
                      {String(schedule.status || 'N/A')}
                    </span>
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

export default ReceptionistSchedule;