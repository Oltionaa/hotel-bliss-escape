import React, { useEffect, useState } from 'react';
import axios from 'axios';

export default function AvailableRooms() {
  const [availableRooms, setAvailableRooms] = useState([]);

  useEffect(() => {
    axios.post('/api/available-rooms', {
      check_in: '2025-04-20',
      check_out: '2025-04-22',
      adults: 2,
      kids: 1
    }).then(res => {
      setAvailableRooms(res.data); // ruaj dhomat në state
    }).catch(err => {
      console.error("Gabim gjatë marrjes së dhomave:", err);
    });
  }, []);

  return (
    <div className="container mt-5">
      <h2>Dhomat e Lira</h2>
      <div className="row">
        {availableRooms.map((room, index) => (
          <div className="col-md-4 mb-4" key={index}>
            <div className="card h-100">
              <img src={room.image} className="card-img-top" alt={room.title} />
              <div className="card-body">
                <h5 className="card-title">{room.title}</h5>
                <p className="card-text">{room.description}</p>
                <a href="#" className="btn btn-primary">BOOK FROM ${room.price}</a>
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
