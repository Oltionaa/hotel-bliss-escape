import React, { useState, useEffect } from 'react';
import { Container, Row, Spinner, Alert } from 'react-bootstrap';
import BookingForm from '../BookingForm';
import RoomCard from '../RoomCard';
import api from '../services/api';

function RoomsPage() {
  const [rooms, setRooms] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchRooms = async () => {
      try {
        const response = await api.get('/rooms');
        setRooms(response.data);
      } catch (err) {
        setError(err.message);
      } finally {
        setLoading(false);
      }
    };

    fetchRooms();
  }, []);

  if (loading) return <Spinner animation="border" />;
  if (error) return <Alert variant="danger">{error}</Alert>;

  return (
    <Container className="py-5">
      <BookingForm />
      <Row>
        {rooms.map(room => (
          <RoomCard key={room.id} room={room} />
        ))}
      </Row>
    </Container>
  );
}

export default RoomsPage;