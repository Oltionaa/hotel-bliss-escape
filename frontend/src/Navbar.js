import { Link } from 'react-router-dom';

function Navbar() {
  return (
    <nav className="navbar navbar-expand-lg navbar-dark bg-dark px-5">
      <Link className="navbar-brand fw-bold" to="/">
        Hotel Bliss Escape
      </Link>
      <div className="collapse navbar-collapse justify-content-end">
        <ul className="navbar-nav">
          <li className="nav-item mx-2"><Link className="nav-link" to="/">Home</Link></li>
          <li className="nav-item mx-2"><a className="nav-link" href="#booking">Reservations</a></li>
          <li className="nav-item mx-2"><a className="nav-link" href="#about">About Us</a></li>
          <li className="nav-item mx-2"><Link className="nav-link" to="/login">Login</Link></li>
        </ul>
      </div>
    </nav>
  );
}

export default Navbar;
