import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';
import { Button, Table, Form, Alert } from 'react-bootstrap';

const ReceptionistDashboard = () => {
  const [reservations, setReservations] = useState([]);
  const [rooms, setRooms] = useState([]);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const [editingReservation, setEditingReservation] = useState(null);
  const [formData, setFormData] = useState({
    customer_name: '',
    check_in: '',
    check_out: '',
    room_id: '',
    status: 'pending',
    user_id: null,
  });
  const navigate = useNavigate();

  useEffect(() => {
    const token = localStorage.getItem('token');
    const userType = localStorage.getItem('userType')?.trim().toLowerCase();
    console.log('ReceptionistDashboard: token:', token, 'userType:', userType);

    if (!token || userType !== 'receptionist') {
      console.log('Redirecting to login: No token or wrong userType');
      localStorage.removeItem('token');
      localStorage.removeItem('user_id');
      localStorage.removeItem('userType');
      navigate('/login', { replace: true });
      return;
    }

    const fetchReservations = async () => {
      setLoading(true);
      try {
        const response = await axios.get('http://localhost:8000/api/receptionist/reservations', {
          headers: { Authorization: `Bearer ${token}` },
        });
        console.log('Reservations response:', response.data);
        setReservations(response.data.reservations || []);
        setError('');
      } catch (error) {
        console.error('Error fetching reservations:', error.response?.data || error);
        if (error.response?.status === 401 || error.response?.status === 403) {
          console.log('Unauthorized or Forbidden: Redirecting to login');
          localStorage.removeItem('token');
          localStorage.removeItem('user_id');
          localStorage.removeItem('userType');
          navigate('/login', { replace: true });
        } else {
          setError(error.response?.data?.message || 'Gabim gjatë marrjes së rezervimeve');
        }
      } finally {
        setLoading(false);
      }
    };

    const fetchRooms = async () => {
      setLoading(true);
      try {
        const response = await axios.get('http://localhost:8000/api/rooms', {
          headers: { Authorization: `Bearer ${token}` },
        });
        console.log('Rooms response:', response.data);
        setRooms(response.data || []);
        setError('');
      } catch (error) {
        console.error('Error fetching rooms:', error.response?.data || error);
        if (error.response?.status === 401 || error.response?.status === 403) {
          console.log('Unauthorized or Forbidden: Redirecting to login');
          localStorage.removeItem('token');
          localStorage.removeItem('user_id');
          localStorage.removeItem('userType');
          navigate('/login', { replace: true });
        } else {
          setError(error.response?.data?.message || 'Gabim gjatë marrjes së dhomave');
        }
      } finally {
        setLoading(false);
      }
    };

    fetchReservations();
    fetchRooms();
  }, [navigate]);

  const handleFormChange = (e) => {
    const { name, value } = e.target;
    setFormData((prevData) => ({
      ...prevData,
      [name]: value,
    }));
  };

  const handleSave = async (e) => {
    e.preventDefault();
    if (new Date(formData.check_out) <= new Date(formData.check_in)) {
      setError('Data e check-out duhet të jetë pas check-in.');
      return;
    }
    setLoading(true);
    try {
      const token = localStorage.getItem('token');
      if (editingReservation) {
        const response = await axios.put(
          `http://localhost:8000/api/receptionist/reservations/${editingReservation.id}`,
          formData,
          { headers: { Authorization: `Bearer ${token}` } }
        );
        setReservations(
          reservations.map((r) =>
            r.id === response.data.reservation.id ? response.data.reservation : r
          )
        );
        setEditingReservation(null);
      } else {
        const response = await axios.post(
          'http://localhost:8000/api/receptionist/reservations',
          formData,
          { headers: { Authorization: `Bearer ${token}` } }
        );
        setReservations([...reservations, response.data.reservation]);
      }
      setFormData({
        customer_name: '',
        check_in: '',
        check_out: '',
        room_id: '',
        status: 'pending',
        user_id: null,
      });
      setError('');
    } catch (error) {
      console.error('Error saving reservation:', error.response?.data || error);
      if (error.response?.status === 401 || error.response?.status === 403) {
        console.log('Unauthorized or Forbidden: Redirecting to login');
        localStorage.removeItem('token');
        localStorage.removeItem('user_id');
        localStorage.removeItem('userType');
        navigate('/login', { replace: true });
      } else {
        setError(error.response?.data?.message || 'Gabim gjatë ruajtjes së rezervimit');
      }
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async (id) => {
    if (window.confirm('Jeni i sigurt që doni të fshini këtë rezervim?')) {
      setLoading(true);
      try {
        const token = localStorage.getItem('token');
        await axios.delete(`http://localhost:8000/api/receptionist/reservations/${id}`, {
          headers: { Authorization: `Bearer ${token}` },
        });
        setReservations(reservations.filter((r) => r.id !== id));
        setError('');
      } catch (error) {
        console.error('Error deleting reservation:', error.response?.data || error);
        if (error.response?.status === 401 || error.response?.status === 403) {
          console.log('Unauthorized or Forbidden: Redirecting to login');
          localStorage.removeItem('token');
          localStorage.removeItem('user_id');
          localStorage.removeItem('userType');
          navigate('/login', { replace: true });
        } else {
          setError(error.response?.data?.message || 'Gabim gjatë fshirjes së rezervimit');
        }
      } finally {
        setLoading(false);
      }
    }
  };

  const handleEdit = (reservation) => {
    setEditingReservation(reservation);
    setFormData({
      customer_name: reservation.customer_name,
      check_in: reservation.check_in,
      check_out: reservation.check_out,
      room_id: reservation.room_id,
      status: reservation.status,
      user_id: reservation.user_id,
    });
  };

  const handleToggleStatus = async (id, currentStatus) => {
    setLoading(true);
    try {
      const token = localStorage.getItem('token');
      const newStatus = currentStatus === 'clean' ? 'dirty' : 'clean';
      const response = await axios.put(
        `http://localhost:8000/api/receptionist/rooms/${id}/status`,
        { status: newStatus },
        { headers: { Authorization: `Bearer ${token}` } }
      );
      setRooms(rooms.map((room) => (room.id === id ? response.data.room : room)));
      setError('');
    } catch (error) {
      console.error('Error toggling room status:', error.response?.data || error);
      if (error.response?.status === 401 || error.response?.status === 403) {
        console.log('Unauthorized or Forbidden: Redirecting to login');
        localStorage.removeItem('token');
        localStorage.removeItem('user_id');
        localStorage.removeItem('userType');
        navigate('/login', { replace: true });
      } else {
        setError(error.response?.data?.message || 'Gabim gjatë ndryshimit të statusit të dhomës');
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="container py-5">
      <h2 className="mb-4 text-center">Paneli i Recepsionistit</h2>

      {error && (
        <Alert variant="danger" className="mb-4">
          {error}
        </Alert>
      )}

      {loading && <div className="text-center mb-4">Duke ngarkuar...</div>}

      <div className="card mb-5 shadow-sm">
        <div className="card-body">
          <h4 className="card-title mb-4">
            {editingReservation ? 'Përditëso Rezervimin' : 'Krijo Rezervim të Ri'}
          </h4>
          <Form onSubmit={handleSave}>
            <Form.Group className="mb-3">
              <Form.Label>Emri i Klientit</Form.Label>
              <Form.Control
                type="text"
                name="customer_name"
                value={formData.customer_name}
                onChange={handleFormChange}
                required
                disabled={loading}
              />
            </Form.Group>
            <Form.Group className="mb-3">
              <Form.Label>Dhoma</Form.Label>
              <Form.Select
                name="room_id"
                value={formData.room_id}
                onChange={handleFormChange}
                required
                disabled={loading}
              >
                <option value="">Zgjidh një dhomë</option>
                {rooms.map((room) => (
                  <option key={room.id} value={room.id}>
                    {room.room_number} - {room.name}
                  </option>
                ))}
              </Form.Select>
            </Form.Group>
            <Form.Group className="mb-3">
              <Form.Label>Data e Check-In</Form.Label>
              <Form.Control
                type="date"
                name="check_in"
                value={formData.check_in}
                onChange={handleFormChange}
                required
                disabled={loading}
              />
            </Form.Group>
            <Form.Group className="mb-3">
              <Form.Label>Data e Check-Out</Form.Label>
              <Form.Control
                type="date"
                name="check_out"
                value={formData.check_out}
                onChange={handleFormChange}
                required
                disabled={loading}
              />
            </Form.Group>
            <Form.Group className="mb-3">
              <Form.Label>Statusi</Form.Label>
              <Form.Select
                name="status"
                value={formData.status}
                onChange={handleFormChange}
                required
                disabled={loading}
              >
                <option value="pending">Në pritje</option>
                <option value="confirmed">Konfirmuar</option>
                <option value="cancelled">Anuluar</option>
              </Form.Select>
            </Form.Group>
            <Button variant="primary" type="submit" className="w-100" disabled={loading}>
              {editingReservation ? 'Përditëso' : 'Krijo'}
            </Button>
            {editingReservation && (
              <Button
                variant="secondary"
                className="w-100 mt-2"
                onClick={() => setEditingReservation(null)}
                disabled={loading}
              >
                Anulo
              </Button>
            )}
          </Form>
        </div>
      </div>

      <div className="card mb-5 shadow-sm">
        <div className="card-body">
          <h4 className="card-title mb-4">Lista e Rezervimeve</h4>
          {reservations.length === 0 ? (
            <p className="text-muted">Nuk ka rezervime.</p>
          ) : (
            <Table striped bordered hover responsive>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Klienti</th>
                  <th>Dhoma</th>
                  <th>Check-In</th>
                  <th>Check-Out</th>
                  <th>Statusi</th>
                  <th>Veprime</th>
                </tr>
              </thead>
              <tbody>
                {reservations.map((res) => (
                  <tr key={res.id}>
                    <td>{res.id}</td>
                    <td>{res.customer_name}</td>
                    <td>{res.room ? `${res.room.room_number} - ${res.room.name}` : 'N/A'}</td>
                    <td>{res.check_in}</td>
                    <td>{res.check_out}</td>
                    <td>
                      <span
                        className={`badge ${
                          res.status === 'confirmed'
                            ? 'bg-success'
                            : res.status === 'pending'
                            ? 'bg-warning'
                            : 'bg-danger'
                        }`}
                      >
                        {res.status === 'confirmed'
                          ? 'Konfirmuar'
                          : res.status === 'pending'
                          ? 'Në pritje'
                          : 'Anuluar'}
                      </span>
                    </td>
                    <td>
                      <Button
                        variant="warning"
                        size="sm"
                        className="me-2"
                        onClick={() => handleEdit(res)}
                        disabled={loading}
                      >
                        Edito
                      </Button>
                      <Button
                        variant="danger"
                        size="sm"
                        onClick={() => handleDelete(res.id)}
                        disabled={loading}
                      >
                        Fshi
                      </Button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </Table>
          )}
        </div>
      </div>

      <div className="card shadow-sm">
        <div className="card-body">
          <h4 className="card-title mb-4">Statusi i Dhomave</h4>
          <Table striped bordered hover responsive>
            <thead>
              <tr>
                <th>Numri i Dhomës</th>
                <th>Emri</th>
                <th>Statusi</th>
                <th>Rezervuar</th>
                <th>Veprime</th>
              </tr>
            </thead>
            <tbody>
              {rooms.map((room) => (
                <tr key={room.id}>
                  <td>{room.room_number}</td>
                  <td>{room.name}</td>
                  <td>
                    <span
                      className={`badge ${room.status === 'clean' ? 'bg-success' : 'bg-danger'}`}
                    >
                      {room.status === 'clean' ? 'E Pastër' : 'E Papastër'}
                    </span>
                  </td>
                  <td>{room.is_reserved ? 'Po' : 'Jo'}</td>
                  <td>
                    <Button
                      variant="primary"
                      size="sm"
                      onClick={() => handleToggleStatus(room.id, room.status)}
                      disabled={loading}
                    >
                      Ndrysho Statusin
                    </Button>
                  </td>
                </tr>
              ))}
            </tbody>
          </Table>
        </div>
      </div>
    </div>
  );
};

export default ReceptionistDashboard;