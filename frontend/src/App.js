import 'bootstrap/dist/css/bootstrap.min.css';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import Navbar from './Navbar';
import Hero from './Hero';
import BookingForm from './BookingForm';
import RoomsAndSuites from './RoomsAndSuites';
import About from './About';
import Contact from './Contact';
import Login from './Login';

function App() {
  return (
    <Router>
      <div className="App">
        <Navbar />
        <Routes>
          <Route path="/" element={
            <>
              <Hero />
              <BookingForm />
              <RoomsAndSuites />
              <About />
              <Contact />
            </>
          } />
          <Route path="/login" element={<Login />} />
        </Routes>
      </div>
    </Router>
  );
}

export default App;
