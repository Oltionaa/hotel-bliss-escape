import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import 'bootstrap/dist/css/bootstrap.min.css';

function Hero() {
  const [userType, setUserType] = useState(null);
  const [isLoggedIn, setIsLoggedIn] = useState(false); // Gjendja për statusin e kyçjes

  useEffect(() => {
    const updateUserStatus = () => {
      const storedUserType = localStorage.getItem('userType');
      const authToken = localStorage.getItem('token'); // Kontrollojmë edhe authToken-in!

      console.log('Hero (useEffect): Checking status...');
      console.log('  storedUserType:', storedUserType);
      console.log('  authToken:', authToken);

      // Përditësojmë userType dhe isLoggedIn bazuar në praninë e authToken
      if (authToken) {
        // Përdoruesi është i kyçur nëse ka token
        setUserType(storedUserType ? storedUserType.trim().toLowerCase() : null);
        setIsLoggedIn(true);
        console.log('  Status: Logged In as', storedUserType);
      } else {
        // Nëse nuk ka authToken, përdoruesi nuk është i kyçur
        setUserType(null);
        setIsLoggedIn(false);
        console.log('  Status: Not Logged In');
      }
    };

    // Thirr funksionin menjëherë kur komponenti montohet
    updateUserStatus();

    // Dëgjo ndryshimet në localStorage. Kjo siguron që komponenti të reagojë
    // nëse token-i fshihet ose shtohet nga një komponent tjetër (p.sh., nga Login ose Logout)
    window.addEventListener('storage', updateUserStatus);

    // Pastro event listener kur komponenti çmontohet për të parandaluar rrjedhjet e memories
    return () => {
      window.removeEventListener('storage', updateUserStatus);
    };
  }, []); // Varet nga asgjë, kështu që ekzekutohet vetëm një herë pas montimit inicial

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

          {/* Këtu lëmë VETËM butonat e dashboard-it NËSE jemi të kyçur */}
          {isLoggedIn && (
            <>
              {userType === 'cleaner' && (
                <Link to="/cleaner-dashboard" className="btn btn-outline-light">
                  Cleaner Dashboard
                </Link>
              )}

              {userType === 'receptionist' && (
                <Link to="/receptionist-dashboard" className="btn btn-outline-light">
                  Receptionist Dashboard
                </Link>
              )}

              {userType === 'admin' && (
                <Link to="/admin-dashboard" className="btn btn-outline-light">
                  Admin Dashboard
                </Link>
              )}
            </>
          )}

          {/* Ky është blloku që heqim: */}
          {/* {!isLoggedIn && (
            <Link to="/login" className="btn btn-outline-light">
              Login
            </Link>
          )} */}
        </div>
      </div>
    </div>
  );
}

export default Hero;