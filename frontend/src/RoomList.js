import React, { useState } from 'react';
import BookingForm from './BookingForm';
import RoomCard from './RoomCard';
import { Container, Row, Alert } from 'react-bootstrap';
import { getAvailableRooms } from '../../api/hotelApi';

function RoomsList() {
  const [rooms, setRooms] = useState([]);
  const [error, setError] = useState(null);

  const handleSearch = async (searchParams) => {
    try {
      const availableRooms = await getAvailableRooms(searchParams);
      setRooms(availableRooms);
    } catch (err) {
      setError(err.message);
    }
  };

  return (
    <Container className="my-5">
      <BookingForm onSearch={handleSearch} />
      
      {error && <Alert variant="danger">{error}</Alert>}
      
      <Row>
        {rooms.map(room => (
          <RoomCard key={room.id} room={room} />
        ))}
      </Row>
    </Container>
  );
}

export default RoomsList;