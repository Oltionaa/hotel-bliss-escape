import React, { useState, useEffect } from 'react';
import { useNavigate, Routes, Route, Navigate, Outlet } from 'react-router-dom';
import 'bootstrap/dist/css/bootstrap.min.css';

import Navbar from './Navbar';
import Hero from './Hero';
import BookingForm from './BookingForm';
import RoomsAndSuites from './RoomsAndSuites';
import About from './About';
import Contact from './Contact';
import Login from './Login';
import UserDashboard from './UserDashboard';
import Pagesat from './Pagesat';
import Confirmation from './Confirmation';
import CleanerDashboard from './CleanerDashboard';
import ReceptionistDashboard from './ReceptionistDashboard';
import AdminDashboard from './AdminDashboard';
import UsersList from './UsersList';
import CreateUser from './CreateUser';
import EditUser from './EditUser';
import ReceptionistSchedule from "./ReceptionistSchedule"; 
import CleanerSchedule from './CleanerSchedule';

function App() {
  const [rooms, setRooms] = useState([]);
  const [errorMessage, setErrorMessage] = useState('');
  const [formData, setFormData] = useState({
    capacity: '1',
    date: '',
    checkOutDate: '',
  });
  const navigate = useNavigate();

  const [authToken, setAuthToken] = useState(null);

  useEffect(() => {
    const storedToken = localStorage.getItem('token'); 
    if (storedToken) {
      setAuthToken(storedToken);
    }
  }, []); 

  const handleSearch = async () => {
    setErrorMessage('');
    console.log('Form Data:', formData);
    try {
      const response = await fetch('http://localhost:8000/api/search-rooms', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify(formData),
      });
      const data = await response.json();
      console.log('API Response:', data);
      if (response.ok && Array.isArray(data)) {
        setRooms(data);
        setErrorMessage('');
      } else {
        setErrorMessage(data.error || 'Përjetuam një problem, ju lutem provoni përsëri.');
        setRooms([]);
      }
    } catch (error) {
      console.error('Gabim në kërkim:', error);
      setErrorMessage('Përjetuam një problem, ju lutem provoni përsëri.');
      setRooms([]);
    }
  };

  const handleFormChange = (e) => {
    const { name, value } = e.target;
    setFormData((prevData) => ({
      ...prevData,
      [name]: value,
    }));
  };

  const handleBookNow = (room) => {
    console.log('Book Now clicked for room:', room);
    navigate('/payments', {
      state: {
        roomId: room.id,
        roomTitle: room.name,
        checkIn: formData.date || new Date().toISOString().split('T')[0],
        checkOut:
          formData.checkOutDate ||
          new Date(new Date().setDate(new Date().getDate() + 1))
            .toISOString()
            .split('T')[0],
      },
    });
  };

  const AdminRoute = () => {
    const token = localStorage.getItem('token');
    const userType = localStorage.getItem('userType')?.trim().toLowerCase();
    // Konfirmo që tokeni ekziston dhe roli është 'admin'
    if (!token || userType !== 'admin') {
      console.log('AdminRoute: Po ridrejtohet te /login - autorizim i dështuar');
      return <Navigate to="/login" replace />;
    }
    return <Outlet />; // Shfaq komponentin fëmijë (dashboard-in, etj.)
  };

  const ReceptionistRoute = () => {
    const token = localStorage.getItem('token');
    const userType = localStorage.getItem('userType')?.trim().toLowerCase();
    // Konfirmo që tokeni ekziston dhe roli është 'receptionist'
    if (!token || userType !== 'receptionist') {
      console.log('ReceptionistRoute: Po ridrejtohet te /login - autorizim i dështuar');
      return <Navigate to="/login" replace />;
    }
    return <Outlet />;
  };

  const CleanerRoute = () => {
    const token = localStorage.getItem('token');
    const userType = localStorage.getItem('userType')?.trim().toLowerCase();
    // Konfirmo që tokeni ekziston dhe roli është 'cleaner'
    if (!token || userType !== 'cleaner') {
      console.log('CleanerRoute: Po ridrejtohet te /login - autorizim i dështuar');
      return <Navigate to="/login" replace />;
    }
    return <Outlet />;
  };

  const UserRoute = () => {
    const token = localStorage.getItem('token');
    const userType = localStorage.getItem('userType')?.trim().toLowerCase();
    // Konfirmo që tokeni ekziston dhe roli është 'user'
    if (!token || userType !== 'user') {
      console.log('UserRoute: Po ridrejtohet te /login - autorizim i dështuar');
      return <Navigate to="/login" replace />;
    }
    return <Outlet />;
  };

  return (
    <div className="App">
      <Navbar />
      <Routes>
        <Route
          path="/"
          element={
            <>
              <Hero />
              <BookingForm
                onSearch={handleSearch}
                onChange={handleFormChange}
                formData={formData}
              />
              {errorMessage && (
                <div className="container py-5 text-center text-danger">
                  {errorMessage}
                </div>
              )}
              {rooms.length > 0 ? (
                <div className="container py-5">
                  <h4 className="mb-4">
                    Dhoma në dispozicion për {formData.date || 'N/A'} deri më{' '}
                    {formData.checkOutDate || 'N/A'}
                  </h4>
                  <div className="row">
                    {rooms.map((room) => (
                      <div className="col-md-4 mb-4" key={room.id}>
                        <div className="card h-100 shadow-sm">
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
                            <h5 className="card-title">{room.name || 'Pa emër'}</h5>
                            <p className="card-text">
                              {room.description || 'Nuk ka përshkrim në dispozicion'}
                            </p>
                            <div className="d-flex justify-content-between text-muted mb-2">
                              <small>
                                <i className="bi bi-fullscreen"></i> MADHËSIA{' '}
                                {room.size || 'N/A'} m²
                              </small>
                              <small>
                                <i className="bi bi-people"></i> MAX{' '}
                                {room.capacity || 'N/A'} persona
                              </small>
                            </div>
                            <p className="fw-bold">€{room.price || 'N/A'}</p>
                            <button
                              className="btn btn-dark w-100"
                              onClick={() => handleBookNow(room)}
                            >
                              REZERVONI TANI
                            </button>
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              ) : (
                <div className="container py-5">
                  <h4>Asnjë dhomë në dispozicion për kërkimin tuaj.</h4>
                </div>
              )}
              <RoomsAndSuites rooms={rooms} />
              <About />
              <Contact />
            </>
          }
        />

        <Route path="/login" element={<Login />} />
        <Route element={<UserRoute />}>
          <Route path="/dashboard" element={<UserDashboard />} />
        </Route>

        <Route path="/payments" element={<Pagesat />} />
        <Route path="/roomsandsuites" element={<RoomsAndSuites />} />
        <Route path="/about" element={<About />} />
        <Route path="/confirmation" element={<Confirmation />} />

        <Route element={<CleanerRoute />}>
          <Route path="/cleaner-dashboard" element={<CleanerDashboard />} />
           <Route path="/cleaner/schedules" element={<CleanerSchedule authToken={authToken} />} />
        </Route>

        <Route element={<ReceptionistRoute />}>
          <Route path="/receptionist-dashboard" element={<ReceptionistDashboard />} />
          <Route path="/receptionist-schedules" element={<ReceptionistSchedule authToken={authToken} />} />
        </Route>

        <Route element={<AdminRoute />}>
          <Route path="/admin-dashboard" element={<AdminDashboard />} />
          <Route path="/users" element={<UsersList />} />
          <Route path="/users/create" element={<CreateUser />} />
          <Route path="/users/edit/:id" element={<EditUser />} />
        </Route>
      </Routes>
    </div>
  );
}

export default App;