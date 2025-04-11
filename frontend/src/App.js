import 'bootstrap/dist/css/bootstrap.min.css';
import Navbar from './Navbar';
import Hero from './Hero';
import BookingForm from './BookingForm';
import RoomsAndSuites from './RoomsAndSuites';

function App() {
  return (
    <div className="App">
      <Navbar />
      <Hero />
      <BookingForm />
      <RoomsAndSuites />
    </div>
  );
}

export default App;

