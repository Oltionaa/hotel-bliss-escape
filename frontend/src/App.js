import 'bootstrap/dist/css/bootstrap.min.css';
import Navbar from './Navbar';
import Hero from './Hero';
import BookingForm from './BookingForm';
import RoomsAndSuites from './RoomsAndSuites';
import About from './About';
import Contact from './Contact';

function App() {
  return (
    <div className="App">
      <Navbar />
      <Hero />
      <BookingForm />
      <RoomsAndSuites />
      <About />
      <Contact />
    </div>
  );
}

export default App;