import 'bootstrap/dist/css/bootstrap.min.css';
import Navbar from './Navbar';
import Hero from './Hero';
import BookingForm from './BookingForm';
import RoomsAndSuites from './RoomsAndSuites';
import React from 'react';
import AuthPage from './AuthPage';

function App() {
  return (
    <div className="App">
      <Navbar />
      <Hero />
      <BookingForm />
      <RoomsAndSuites />
      <AuthPage />
    </div>
  );
}

export default App;
