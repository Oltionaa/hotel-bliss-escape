import "bootstrap/dist/css/bootstrap.min.css";
import { BrowserRouter as Router, Routes, Route } from "react-router-dom";
import Navbar from "./Navbar";
import Hero from "./Hero";
import BookingForm from "./BookingForm";
import RoomsAndSuites from "./RoomsAndSuites";
import About from "./About";
import Contact from "./Contact";
import Login from "./Login";
import Dashboard from "./Dashboard";
import Pagesat from "./Pagesat";
import { Link } from 'react-router-dom';

import { useEffect, useState } from "react";

function App() {
  const [rooms, setRooms] = useState([]); 
  const [errorMessage, setErrorMessage] = useState(""); 
  const [formData, setFormData] = useState({
    capacity: "1", 
    date: "",
  });

  const handleSearch = async () => {
    setErrorMessage(""); 
    console.log("Form Data:", formData); 
    try {
      const response = await fetch("http://localhost:8000/api/search-rooms", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "Accept": "application/json",
        },
        body: JSON.stringify(formData),
      });
  
      const data = await response.json();
      console.log("API Response:", data); 
  

      const filteredRooms = data.filter((room) => {
        return room.capacity >= parseInt(formData.capacity);
      });
  
      setRooms(filteredRooms); 
      setErrorMessage("");
  
    } catch (error) {
      console.error("Gabim:", error);
      setErrorMessage("Përjetuam një problem, ju lutem provoni përsëri.");
    }
  };

  const handleFormChange = (e) => {
    const { name, value } = e.target;
    setFormData((prevData) => ({
      ...prevData,
      [name]: value,
    }));
  };

  return (
    <Router>
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
                  <div className="alert alert-danger">{errorMessage}</div>
                )}{rooms && rooms.length > 0 ? (
                  <div className="container py-5">
                    <h4 className="mb-4">Available Rooms</h4>
                    <div className="row">
                      {rooms.map((room) => (
                        <div className="col-md-4 mb-4" key={room.id}>
                          <div className="card h-100 shadow-sm">
                            {}
                            <img 
                                src={room.image ? `http://localhost:8000/storage/rooms/${room.image}` : 'https://via.placeholder.com/400x250'}

                                className="card-img-top" 
                                alt={room.name || 'Room'} 
                                style={{ height: '250px', objectFit: 'cover' }}
                              />

                            <div className="card-body">
                              {}
                              <h5 className="card-title">{room.name || room.title}</h5>
                              {}
                              <p className="card-text">{room.description || room.pershkrimi}</p>
                              <div className="d-flex justify-content-between text-muted mb-2">
                                {}
                                <small><i className="bi bi-fullscreen"></i> SIZE {room.size || room.madhesia} m²</small>
                                {}
                                <small><i className="bi bi-people"></i> MAX {room.capacity || room.kapaciteti} people</small>
                              </div>
                              {}
                              <p className="fw-bold">€{room.price || room.cmimi}</p>
                              {}
                              <Link to="/payments" className="btn btn-dark w-100">BOOK NOW</Link>
                            </div>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                ) : (
                  <div className="container py-5">
                    <h4>No rooms available for your search.</h4>
                  </div>
                )}
                
                <RoomsAndSuites rooms={rooms} />
                <About />
                <Contact />
              </>
            }
          />
          <Route path="/login" element={<Login />} />
          <Route path="/dashboard" element={<Dashboard />} />
          <Route path="/payments" element={<Pagesat />} />
          <Route path="/roomsandsuites" element={<RoomsAndSuites />} />
          <Route path="/about" element={<About />} />
        </Routes>
      </div>
    </Router>
  );
}

export default App;