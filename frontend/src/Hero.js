import React from 'react';
import { Link } from 'react-router-dom';
import 'bootstrap/dist/css/bootstrap.min.css';

function Hero() {
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
        <Link to="/roomsandsuites" className="btn btn-dark">
          See Our Rooms
        </Link>
      </div>
    </div>
  );
}

export default Hero;
