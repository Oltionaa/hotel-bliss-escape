import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { useNavigate } from 'react-router-dom';

const CleanerDashboard = () => {
  const [rooms, setRooms] = useState([]);
  const [message, setMessage] = useState('');
  const [loading, setLoading] = useState(true);
  const navigate = useNavigate();

  useEffect(() => {
    const token = localStorage.getItem('token');
    const userType = localStorage.getItem('userType')?.trim().toLowerCase();
    console.log('CleanerDashboard: Token:', token, 'UserType:', userType);

    if (!token || userType !== 'cleaner') {
      console.log('CleanerDashboard: Unauthorized, redirecting to login');
      localStorage.removeItem('token');
      localStorage.removeItem('user_id');
      localStorage.removeItem('userType');
      navigate('/login', { replace: true });
      return;
    }

    // Logjika e fetchDirtyRooms zhvendoset këtu
    setLoading(true);
    axios.get('http://localhost:8000/sanctum/csrf-cookie')
      .then(() => {
        axios.get('http://localhost:8000/api/cleaner/rooms', {
          headers: { Authorization: `Bearer ${token}` },
        })
          .then((response) => {
            console.log('CleanerDashboard: Fetch response:', response.data);
            setRooms(response.data || []);
            setMessage('');
            setLoading(false);
          })
          .catch((error) => {
            console.error('CleanerDashboard: Error fetching rooms:', error.response?.data || error);
            console.error('CleanerDashboard: Status:', error.response?.status);
            console.error('CleanerDashboard: Data:', error.response?.data);
            if (error.response?.status === 401) {
              setMessage('Sesioni juaj ka skaduar. Ju lutemi hyni përsëri.');
              localStorage.removeItem('token');
              localStorage.removeItem('user_id');
              localStorage.removeItem('userType');
              navigate('/login', { replace: true });
            } else if (error.response?.status === 403) {
              setMessage('Nuk keni autorizim për të parë dhomat.');
            } else {
              setMessage(error.response?.data?.error || 'Gabim në marrjen e dhomave.');
            }
            setLoading(false);
          });
      })
      .catch((error) => {
        console.error('CleanerDashboard: Error fetching CSRF cookie:', error);
        setMessage('Gabim gjatë konfigurimit të kërkesës. Provoni përsëri.');
        setLoading(false);
      });
  }, [navigate]);

  const markAsClean = async (roomId) => {
    const token = localStorage.getItem('token');
    try {
      console.log('CleanerDashboard: Marking room as clean:', roomId);
      await axios.get('http://localhost:8000/sanctum/csrf-cookie');
      const response = await axios.put(
        `http://localhost:8000/api/cleaner/rooms/${roomId}/clean`,
        {},
        {
          headers: { Authorization: `Bearer ${token}` },
        }
      );
      console.log('CleanerDashboard: Mark as clean response:', response.data);
      setRooms((prevRooms) => prevRooms.filter((room) => room.id !== roomId));
      setMessage('Dhomë e pastruar me sukses!');
    } catch (error) {
      console.error('CleanerDashboard: Error marking room:', error.response?.data || error);
      console.error('CleanerDashboard: Status:', error.response?.status);
      setMessage(error.response?.data?.error || 'Gabim gjatë pastrimit të dhomës.');
    }
  };

  return (
    <div className="container mt-4">
      <h2>Dhomat që janë për pastrim</h2>

      {message && (
        <div className={`alert ${message.includes('sukses') ? 'alert-success' : 'alert-danger'}`}>
          {message}
        </div>
      )}

      {loading ? (
        <div className="text-center">
          <p>Duke ngarkuar...</p>
        </div>
      ) : (
        <div className="row">
          {rooms.length === 0 ? (
            <p className="text-center">Nuk ka dhoma për pastrim në këtë moment.</p>
          ) : (
            rooms.map((room) => (
              <div className="col-md-4" key={room.id}>
                <div className="card mb-3 shadow-sm">
                  <img
                    src={
                      room.image
                        ? `http://localhost:8000/storage/rooms/${room.image}`
                        : 'https://via.placeholder.com/400x250'
                    }
                    className="card-img-top"
                    alt={room.name || 'Room'}
                    style={{ height: '250px', objectFit: 'cover' }}
                  />
                  <div className="card-body">
                    <h5 className="card-title">{room.name}</h5>
                    <p className="card-text">{room.description}</p>
                    <p>
                      Status: <strong>{room.status}</strong>
                    </p>
                    <p>
                      Room Number: <strong>{room.room_number}</strong>
                    </p>
                    <button
                      className="btn btn-success"
                      onClick={() => markAsClean(room.id)}
                    >
                      markAsClean
                    </button>
                  </div>
                </div>
              </div>
            ))
          )}
        </div>
      )}
    </div>
  );
};

export default CleanerDashboard;