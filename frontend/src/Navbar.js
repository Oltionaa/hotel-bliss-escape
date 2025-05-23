import { Link, useNavigate } from 'react-router-dom';
import axios from 'axios';

function Navbar() {
  const navigate = useNavigate();

  const handleLogout = async () => {
    try {
      const token = localStorage.getItem('token');
      if (!token) {
        alert('No token found. Please log in again.');
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
      navigate('/login');
    } catch (error) {
      const errorMessage = error.response?.data?.message || error.message;
      console.error('Logout failed:', errorMessage);
      alert(`Failed to logout: ${errorMessage}`);
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
          <li className="nav-item mx-2">
            <button className="nav-link btn btn-link" onClick={handleLogout}>
              Logout
            </button>
          </li>
        </ul>
      </div>
    </nav>
  );
}

export default Navbar;