import React from "react";
import { useLocation } from "react-router-dom";
import "bootstrap/dist/css/bootstrap.min.css";

const Confirmation = () => {
  const location = useLocation();
  const {
    reservationDetails,
    paymentDetails,
    roomTitle,
    checkIn,
    checkOut,
  } = location.state || {};

  console.log("Confirmation state:", {
    reservationDetails,
    paymentDetails,
    roomTitle,
    checkIn,
    checkOut,
  });

  if (
    !reservationDetails ||
    !paymentDetails ||
    !roomTitle ||
    !checkIn ||
    !checkOut
  ) {
    return (
      <div className="container mt-5 text-center text-danger">
        No confirmation data available. Please try again.
      </div>
    );
  }

  return (
    <div className="container mt-5" style={{ maxWidth: "600px" }}>
      <div className="card shadow-sm p-4">
        <h3 className="text-center mb-4">Reservation Confirmed 🎉</h3>
        <h5 className="mb-3">Reservation Details</h5>
        <p>
          <strong>Room:</strong> {roomTitle}
        </p>
        <p>
          <strong>Customer Name:</strong> {reservationDetails.customer_name}
        </p>
        <p>
          <strong>Check-In:</strong> {checkIn}
        </p>
        <p>
          <strong>Check-Out:</strong> {checkOut}
        </p>
        <p>
          <strong>Status:</strong> {reservationDetails.status}
        </p>
        <h5 className="mb-3 mt-4">Payment Details</h5>
        <p>
          <strong>Cardholder:</strong> {paymentDetails.cardholder}
        </p>
        <p>
          <strong>Bank Name:</strong> {paymentDetails.bank_name}
        </p>
        <p>
          <strong>Card Type:</strong> {paymentDetails.card_type}
        </p>
        <p>
          <strong>Card Number (Last 4):</strong> {paymentDetails.card_number.slice(-4)}
        </p>
        <div className="text-center mt-4">
          <a href="/" className="btn btn-dark">
            Back to Home
          </a>
        </div>
      </div>
    </div>
  );
};

export default Confirmation;