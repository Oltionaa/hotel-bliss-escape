import "bootstrap/dist/css/bootstrap.min.css";
import { BrowserRouter as Router, Routes, Route } from "react-router-dom";
import Navbar from "./Navbar";
import Hero from "./Hero";
import BookingForm from "./BookingForm";
import RoomsAndSuites from "./RoomsAndSuites";
import About from "./About";
import Contact from "./Contact";
import Login from "./Login";
import Dashboard from './Dashboard';
import Pagesat from './Pagesat';
import { useEffect, useState } from "react";

function App() {
  const [apiData, setApiData] = useState(null);

  useEffect(() => {
    // This fetch call runs once when the app loads
    fetch("http://localhost:8000/api/test")
      .then((response) => response.json())
      .then((data) => {
        console.log("API Response:", data); // Check console
        setApiData(data); // Store response in state (optional)
      })
      .catch((error) => console.error("Error:", error));
  }, []);

  return (
    <Router>
      <div className="App">
        <Navbar />
        {/* Optional: show API response at the top */}
        {apiData && (
          <div className="alert alert-success text-center">
            API Connected: {JSON.stringify(apiData)}
          </div>
        )}
        <Routes>
          <Route
            path="/"
            element={
              <>
                <Hero />
                <BookingForm />
                <RoomsAndSuites />
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
