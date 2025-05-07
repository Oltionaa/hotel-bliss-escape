import { useState } from "react";
import { useNavigate, Routes, Route } from "react-router-dom";
import "bootstrap/dist/css/bootstrap.min.css";
import Navbar from "./Navbar";
import Hero from "./Hero";
import BookingForm from "./BookingForm";
import RoomsAndSuites from "./RoomsAndSuites";
import About from "./About";
import Contact from "./Contact";
import Login from "./Login";
import UserDashboard from "./UserDashboard"; // Zëvendësuar Dashboard me UserDashboard
import Pagesat from "./Pagesat";
import Confirmation from "./Confirmation";

function App() {
  const [rooms, setRooms] = useState([]);
  const [errorMessage, setErrorMessage] = useState("");
  const [formData, setFormData] = useState({
    capacity: "1",
    date: "",
    checkOutDate: "",
  });

  const navigate = useNavigate();

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

      if (response.ok && Array.isArray(data)) {
        setRooms(data);
        setErrorMessage("");
      } else {
        setErrorMessage(data.error || "Përjetuam një problem, ju lutem provoni përsëri.");
        setRooms([]);
      }
    } catch (error) {
      console.error("Gabim në kërkim:", error);
      setErrorMessage("Përjetuam një problem, ju lutem provoni përsëri.");
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
    console.log("Book Now clicked for room:", room);
    navigate("/payments", {
      state: {
        roomId: room.id,
        roomTitle: room.name,
        checkIn: formData.date || new Date().toISOString().split("T")[0],
        checkOut:
          formData.checkOutDate ||
          new Date(new Date().setDate(new Date().getDate() + 1))
            .toISOString()
            .split("T")[0],
      },
    });
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
                    Available Rooms for {formData.date || "N/A"} to{" "}
                    {formData.checkOutDate || "N/A"}
                  </h4>
                  <div className="row">
                    {rooms.map((room) => (
                      <div className="col-md-4 mb-4" key={room.id}>
                        <div className="card h-100 shadow-sm">
                          <img
                            src={
                              room.image
                                ? `http://localhost:8000/storage/rooms/${room.image}`
                                : "https://via.placeholder.com/400x250"
                            }
                            className="card-img-top"
                            alt={room.name || "Room"}
                            style={{ height: "250px", objectFit: "cover" }}
                          />
                          <div className="card-body">
                            <h5 className="card-title">{room.name || "No name"}</h5>
                            <p className="card-text">
                              {room.description || "No description available"}
                            </p>
                            <div className="d-flex justify-content-between text-muted mb-2">
                              <small>
                                <i className="bi bi-fullscreen"></i> SIZE{" "}
                                {room.size || "N/A"} m²
                              </small>
                              <small>
                                <i className="bi bi-people"></i> MAX{" "}
                                {room.capacity || "N/A"} people
                              </small>
                            </div>
                            <p className="fw-bold">€{room.price || "N/A"}</p>
                            <button
                              className="btn btn-dark w-100"
                              onClick={() => handleBookNow(room)}
                            >
                              BOOK NOW
                            </button>
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
        <Route path="/dashboard" element={<UserDashboard />} /> {/* Zëvendësuar Dashboard me UserDashboard */}
        <Route path="/payments" element={<Pagesat />} />
        <Route path="/roomsandsuites" element={<RoomsAndSuites />} />
        <Route path="/about" element={<About />} />
        <Route path="/confirmation" element={<Confirmation />} />
      </Routes>
    </div>
  );
}

export default App;