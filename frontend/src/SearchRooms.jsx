import { useState } from 'react';
import axios from 'axios';

function SearchRooms() {
  const [checkIn, setCheckIn] = useState('');
  const [checkOut, setCheckOut] = useState('');
  const [adults, setAdults] = useState(1);
  const [children, setChildren] = useState(0);
  const [rooms, setRooms] = useState([]);

  const handleSearch = async () => {
    try {
      const response = await axios.post('http://localhost:8000/api/search-rooms', {
        check_in: checkIn,
        check_out: checkOut,
        adults,
        children,
      });
      setRooms(response.data);
    } catch (error) {
      console.error(error);
    }
  };

  return (
    <div className="p-4">
      <input type="date" value={checkIn} onChange={e => setCheckIn(e.target.value)} />
      <input type="date" value={checkOut} onChange={e => setCheckOut(e.target.value)} />
      <input type="number" value={adults} min="1" onChange={e => setAdults(parseInt(e.target.value))} />
      <input type="number" value={children} min="0" onChange={e => setChildren(parseInt(e.target.value))} />
      <button onClick={handleSearch}>Kërko Dhomat</button>

      <div>
        {rooms.length > 0 ? (
          rooms.map(room => (
            <div key={room.id} className="border p-2 mt-2">
              <p>Dhoma: {room.name}</p>
              <p>Kapaciteti: {room.capacity}</p>
            </div>
          ))
        ) : (
          <p>Nuk u gjetën dhoma për këto data.</p>
        )}
      </div>
    </div>
  );
}

export default SearchRooms;