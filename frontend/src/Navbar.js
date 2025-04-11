function Navbar() {
    return (
      <nav className="navbar navbar-expand-lg navbar-dark bg-dark px-5">
        <a className="navbar-brand fw-bold" href="#">
          <img src="public/images/logo.png" alt="Logo" className="me-2" />
          Hotel Bliss Escape
        </a>
        <div className="collapse navbar-collapse justify-content-end">
          <ul className="navbar-nav">
            <li className="nav-item mx-2"><a className="nav-link" href="#">Home</a></li>
            <li className="nav-item mx-2"><a className="nav-link" href="#">Rooms</a></li>
            <li className="nav-item mx-2"><a className="nav-link" href="#">Reservations</a></li>
            <li className="nav-item mx-2"><a className="nav-link" href="#">AboutUs</a></li>
            <li className="nav-item mx-2"><a className="nav-link" href="#">Contact</a></li>
          </ul>
        </div>
      </nav>
    );
  }

  
  
  export default Navbar;