import React from 'react';
import { Link } from 'react-router-dom';  // Shto importin e Link
import 'bootstrap/dist/css/bootstrap.min.css';

const rooms = [
  {
    title: "Luxury Suite Room",
    description: " Our luxury suites offer a perfect blend of sophistication and relaxation.",
    size: "90M2",
    people: 5,
    price: 320,
    image: "https://i.pinimg.com/736x/af/a5/a8/afa5a8a1d9150e477686153e43137da6.jpg"
  },
  {
    title: "Deluxe Room",
    description: "Deluxe Room offers the perfect balance of elegance and comfort.",
    size: "45M2",
    people: 6,
    price: 344,
    image: "https://i.pinimg.com/736x/3b/de/8e/3bde8ebd5eba68e47fefb1d2dece07ab.jpg"
  },
  {
    title: "Luxury Room",
    description: "Enjoy a spacious layout, a plush king-sized bed, and a luxurious marble bathroom.",
    size: "84M2",
    people: 7,
    price: 389,
    image: "https://i.pinimg.com/736x/9e/6c/d2/9e6cd290af807e41ecf11ab96b8151dc.jpg"
  },
  {
    title: "Standard Room",
    description: "Comfortable and cozy with all the basic amenities for a relaxing stay.",
    size: "30M2",
    people: 2,
    price: 120,
    image: "https://i.pinimg.com/736x/2c/7e/f6/2c7ef61082c3e749b5939228d40d04cd.jpg"
  },
  {
    title: "Economy Room",
    description: "A budget-friendly room for a pleasant stay with simple and modern features.",
    size: "35M2",
    people: 3,
    price: 150,
    image: "https://i.pinimg.com/474x/a8/f0/bd/a8f0bdbb55744aeb924024b244a29e85.jpg"
  },
  {
    title: "Basic Room",
    description: "A no-frills room perfect for travelers on a budget with essential amenities.",
    size: "25M2",
    people: 1,
    price: 95,
    image: "https://i.pinimg.com/736x/13/95/91/139591f67ac33de6ba2423f1645994b5.jpg"
  }
];

export default function RoomsAndSuites() {
  return (
    <div className="container py-5">
      <div className="text-center mb-5">
        <h6 className="text-uppercase">Bliss Escape Hotel</h6>
        <h2 className="fw-bold">Rooms & Suites</h2>
      </div>
      <div className="row">
        {rooms.map((room, idx) => (
          <div className="col-md-4 mb-4" key={idx}>
            <div className="card h-100 shadow-sm">
              <img src={room.image} className="card-img-top" alt={room.title} />
              <div className="card-body">
                <div className="d-flex justify-content-between mb-2 text-muted">
                  <small><i className="bi bi-fullscreen"></i> SIZE {room.size}</small>
                  <small><i className="bi bi-people"></i> MAX PEOPLE {room.people}</small>
                </div>
                <h5 className="card-title">{room.title}</h5>
                <p className="card-text">{room.description}</p>
                {/* Përdor Link për navigim te faqja e pagesave */}
                <Link 
                  to="/payments" 
                  className="btn btn-dark w-100"
                  // Opsionale: Kalimi i të dhënave për pagesë si query param (p.sh. price, room title)
                  // to={`/payments?price=${room.price}&title=${encodeURIComponent(room.title)}`}
                >
                  BOOK NOW FROM ${room.price}
                </Link>
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}



