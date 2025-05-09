import React, { useEffect, useState } from 'react';
import axios from 'axios';

const CleanerDashboard = () => {
  const [rooms, setRooms] = useState([]);
  const [message, setMessage] = useState('');
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchDirtyRooms();
  }, []);
  
  const fetchDirtyRooms = async () => {
    setLoading(true); // Aktivizo "loading" kur fillon thirrja
    try {
      const response = await axios.get('http://localhost:8000/api/cleaner/rooms');
      setRooms(response.data); // Ruaj dhomat që kthehen nga API
      setMessage(''); // Çdo mesazh i mëparshëm fshihet
    } catch (error) {
      console.error('Gabim në marrjen e dhomave:', error);
      setMessage('Gabim në marrjen e dhomave, ju lutemi provoni përsëri më vonë.'); // Tregoni mesazh gabimi
    } finally {
      setLoading(false); // Mbyll "loading" kur përfundojmë thirrjen
    }
  };

  const markAsClean = async (roomId) => {
    try {
      await axios.put(`http://localhost:8000/api/cleaner/rooms/${roomId}/clean`);
      // Pasi dhoma është pastruar, përditësojeni listën lokale
      setRooms(prevRooms => prevRooms.filter(room => room.id !== roomId));
      setMessage('Dhomë e pastruar me sukses!'); // Mesazh sukses
    } catch (error) {
      console.error('Gabim gjatë pastrimit të dhomës:', error);
      setMessage('Gabim gjatë pastrimit të dhomës.'); // Mesazh gabimi
    }
  };

  return (
    <div className="container mt-4">
      <h2>Dhomat që janë për pastrim</h2>
      
      {/* Trego mesazhin nëse ka */}
      {message && <p>{message}</p>}

      {loading ? (
        <div className="text-center">
          <p>Loading...</p> {/* Kjo është për ngarkimin e dhomave */}
        </div>
      ) : (
        <div className="row">
          {rooms.length === 0 ? (
            <p className="text-center">Nuk ka dhoma për pastrim në këtë moment.</p>
          ) : (
            rooms.map(room => (
              <div className="col-md-4" key={room.id}>
                <div className="card mb-3 shadow-sm">
                  {/* Kontrollo nëse imazhi ekziston */}
                  <img
                            src={
                              room.image
                                ? `http://localhost:8000/storage/rooms/${room.image}`
                                : "https://via.placeholder.com/400x250"
                            }
                            className="card-img-top"
                            alt={room.name || "Room"}
                            style={{ height: "250px", objectFit: "cover" }}
                          />
                  <div className="card-body">
                    <h5 className="card-title">{room.name}</h5>
                    <p className="card-text">{room.description}</p>
                    <p>Status: <strong>{room.status}</strong></p>
                    <p>Room Number: <strong>{room.room_number}</strong></p>
                    <button 
                      className="btn btn-success" 
                      onClick={() => markAsClean(room.id)}>
                      Mark as Clean
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
