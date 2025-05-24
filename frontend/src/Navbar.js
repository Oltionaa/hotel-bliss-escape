import { Link, useNavigate } from 'react-router-dom';
import axios from 'axios';

function Navbar() {
  const navigate = useNavigate();
  const token = localStorage.getItem('token');
  const userType = localStorage.getItem('userType'); // Merr userType nga localStorage

  const handleLogout = async () => {
    try {
      // Nuk përdorim alert(), por console.error ose një modal/toast
      if (!token) {
        console.error('No token found. Please log in again.');
        navigate('/login');
        return;
      }
      await axios.post(
        'http://localhost:8000/api/logout',
        {},
        {
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: 'application/json',
          },
        }
      );
      localStorage.removeItem('token');
      localStorage.removeItem('userType'); // Hiq userType në logout gjithashtu
      localStorage.removeItem('userId'); // Hiq edhe userId
      navigate('/login');
    } catch (error) {
      const errorMessage = error.response?.data?.message || error.message;
      console.error('Logout failed:', errorMessage);
      // alert(`Failed to logout: ${errorMessage}`); // Shmang alert()
    }
  };

  return (
    <nav className="navbar navbar-expand-lg navbar-dark bg-dark px-5">
      <Link className="navbar-brand fw-bold" to="/">
        Hotel Bliss Escape
      </Link>
      <div className="collapse navbar-collapse justify-content-end">
        <ul className="navbar-nav">

          <li className="nav-item mx-2">
            <Link className="nav-link" to="/">
              Home
            </Link>
          </li>

          <li className="nav-item mx-2">
            <Link className="nav-link" to="/dashboard">
              Reservations
            </Link>
          </li>

          <li className="nav-item mx-2">
            <a className="nav-link" href="#about">
              About Us
            </a>
          </li>

          {/* Link vetëm për recepsionistë */}
          {userType?.toLowerCase() === "receptionist" && (
            <li className="nav-item mx-2">
             <Link className="nav-link" to="/receptionist-schedules"> {/* Kujdes: ruta e saktë */}
              Orari Recepsionistit
            </Link>
            </li>
          )}

          {/* Link vetëm për pastrues (Shto këtë!) */}
          {userType?.toLowerCase() === "cleaner" && (
            <li className="nav-item mx-2">
             <Link className="nav-link" to="/cleaner/schedules"> {/* Kujdes: ruta e saktë */}
              Orari Pastruesit
            </Link>
            </li>
          )}

          {/* Login / Logout */}
          {token ? (
            <li className="nav-item mx-2">
              <button className="nav-link btn btn-link" onClick={handleLogout}>
                Logout
              </button>
            </li>
          ) : (
            <li className="nav-item mx-2">
              <Link className="nav-link" to="/login">
                Login
              </Link>
            </li>
          )}

        </ul>
      </div>
    </nav>
  );
}

export default Navbar;