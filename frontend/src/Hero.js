import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import 'bootstrap/dist/css/bootstrap.min.css';

function Hero() {
  const [userType, setUserType] = useState(null);

  useEffect(() => {
    // Merr llojin e përdoruesit nga localStorage
    const updateUserType = () => {
      const storedUserType = localStorage.getItem('userType');
      setUserType(storedUserType ? storedUserType.trim().toLowerCase() : null);
    };

    // Thirr fillimisht
    updateUserType();

    // Dëgjo ndryshimet në localStorage
    window.addEventListener('storage', updateUserType);

    // Pastro event listener kur komponenti çmontohet
    return () => {
      window.removeEventListener('storage', updateUserType);
    };
  }, []);

  return (
    <div
      className="bg-dark text-white text-center d-flex align-items-center justify-content-center"
      style={{
        height: '65vh',
        backgroundImage: `url('https://i.pinimg.com/736x/1e/68/bd/1e68bd1cc4de17f8148eca296748e7f4.jpg')`,
        backgroundSize: 'cover',
        backgroundPosition: 'center',
        position: 'relative',
      }}
    >
      <div className="bg-dark bg-opacity-50 p-5 rounded">
        <p className="text-uppercase small">Just enjoy and relax</p>
        <h1 className="display-4 fw-bold">
          Best Hotel
          <br />
          For Vacation
        </h1>

        <div className="d-flex justify-content-center gap-3 mt-3">
          <Link to="/roomsandsuites" className="btn btn-dark">
            See Our Rooms
          </Link>

          {/* Shfaq button-in vetëm për cleaner */}
          {userType === 'cleaner' && (
            <Link to="/cleaner-dashboard" className="btn btn-outline-light">
              Cleaner Dashboard
            </Link>
          )}
        </div>
      </div>
    </div>
  );
}

export default Hero;