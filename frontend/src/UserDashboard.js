import React, { useState, useEffect } from "react";
import axios from "axios";
import { useNavigate } from "react-router-dom";
import "bootstrap/dist/css/bootstrap.min.css";

const UserDashboard = () => {
  const [reservations, setReservations] = useState([]);
  const [error, setError] = useState("");
  const [editReservationId, setEditReservationId] = useState(null);
  const [editFormData, setEditFormData] = useState({
    check_in: "",
    check_out: "",
  });
  const [isUpdating, setIsUpdating] = useState(false);
  const navigate = useNavigate();

  useEffect(() => {
    const fetchReservations = async () => {
      try {
        const token = localStorage.getItem("token");
        console.log("Token for fetch:", token);
        if (!token) {
          setError("Please log in to view your reservations.");
          navigate("/login");
          return;
        }
        const response = await axios.get("http://localhost:8000/api/reservations/user", {
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
        });
        console.log("API Response:", response.data);
        setReservations(response.data.reservations || []);
      } catch (err) {
        console.error("Fetch error:", err.response?.data || err.message);
        setError("Error fetching reservations: " + (err.response?.data?.message || err.message));
        if (err.response?.status === 401) {
          localStorage.removeItem("token");
          navigate("/login");
        }
      }
    };
    fetchReservations();
  }, [navigate]);

  const handleEditChange = (e) => {
    const { name, value } = e.target;
    setEditFormData((prevData) => ({
      ...prevData,
      [name]: value,
    }));
  };

  const startEditing = (reservation) => {
    setEditReservationId(reservation.id);
    setEditFormData({
      check_in: reservation.check_in,
      check_out: reservation.check_out,
    });
  };

  const saveEdit = async (reservationId) => {
    if (isUpdating) return;
    setIsUpdating(true);
    try {
      const token = localStorage.getItem("token");
      console.log("PUT request, reservationId:", reservationId, "Token:", token);
      if (!token) {
        throw new Error("Missing token. Redirecting to login.");
      }

      const response = await axios.put(
        `http://localhost:8000/api/reservations/${reservationId}`,
        {
          check_in: editFormData.check_in,
          check_out: editFormData.check_out,
        },
        {
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
            "Content-Type": "application/json",
          },
        }
      );
      console.log("Update response:", response.data);
      setReservations(
        reservations.map((res) =>
          res.id === reservationId ? { ...res, ...response.data.reservation } : res
        )
      );
      setEditReservationId(null);
      setError("");
    } catch (error) {
      console.error("Error during update:", error.response?.data || error.message);
      let errorMessage = error.response?.data?.message || error.message;
      if (error.response?.status === 401) {
        errorMessage = "User is not authenticated. Please log in again.";
        localStorage.removeItem("token");
        navigate("/login");
      } else if (error.response?.status === 404) {
        errorMessage = "Reservation not found.";
      } else if (error.response?.status === 403) {
        errorMessage = "You are not authorized to update this reservation.";
      } else if (error.response?.status === 422) {
        errorMessage = "Invalid data provided. Check dates.";
      } else if (error.response?.status === 500) {
        errorMessage = "Server error. Please try again later.";
      }
      setError(errorMessage);
    } finally {
      setIsUpdating(false);
    }
  };

  const cancelReservation = async (reservationId) => {
    if (!window.confirm("Are you sure you want to cancel this reservation?")) return;
    try {
      const token = localStorage.getItem("token");
      console.log("DELETE request, reservationId:", reservationId, "Token:", token);
      if (!token) {
        throw new Error("Missing token. Redirecting to login.");
      }

      await axios.delete(`http://localhost:8000/api/reservations/${reservationId}`, {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
      });
      setReservations(reservations.filter((res) => res.id !== reservationId));
      setError("");
    } catch (err) {
      console.error("Delete error:", err.response?.data || err.message);
      let errorMessage = err.response?.data?.message || err.message;
      if (err.response?.status === 401) {
        errorMessage = "User is not authenticated. Please log in again.";
        localStorage.removeItem("token");
        navigate("/login");
      }
      setError("Error canceling reservation: " + errorMessage);
    }
  };

  return (
    <div className="container mt-5">
      <h2 className="mb-4">My Reservations</h2>
      {error && <div className="alert alert-danger mb-4">{error}</div>}
      {reservations.length === 0 ? (
        <p className="text-muted">You don't have any reservations.</p>
      ) : (
        <div className="row">
          {reservations.map((reservation) => (
            <div key={reservation.id} className="col-md-6 mb-4">
              <div className="card shadow-sm">
                <div className="card-body">
                  <h5 className="card-title">
                    Room: {reservation.room?.title || "N/A"}
                  </h5>
                  <p className="card-text">
                    <strong>Name:</strong> {reservation.customer_name}
                  </p>
                  {editReservationId === reservation.id ? (
                    <div>
                      <div className="mb-3">
                        <label htmlFor="check_in" className="form-label">
                          Check-In Date
                        </label>
                        <input
                          type="date"
                          className="form-control"
                          name="check_in"
                          value={editFormData.check_in}
                          onChange={handleEditChange}
                        />
                      </div>
                      <div className="mb-3">
                        <label htmlFor="check_out" className="form-label">
                          Check-Out Date
                        </label>
                        <input
                          type="date"
                          className="form-control"
                          name="check_out"
                          value={editFormData.check_out}
                          onChange={handleEditChange}
                        />
                      </div>
                      <button
                        className="btn btn-success me-2"
                        onClick={() => saveEdit(reservation.id)}
                        disabled={isUpdating}
                      >
                        Save
                      </button>
                      <button
                        className="btn btn-secondary"
                        onClick={() => setEditReservationId(null)}
                        disabled={isUpdating}
                      >
                        Cancel Edit
                      </button>
                    </div>
                  ) : (
                    <div>
                      <p className="card-text">
                        <strong>Check-In Date:</strong> {reservation.check_in}
                      </p>
                      <p className="card-text">
                        <strong>Check-Out Date:</strong> {reservation.check_out}
                      </p>
                      <p className="card-text">
                        <strong>Status:</strong> {reservation.status}
                      </p>
                      <h6>Payment Details</h6>
                      {reservation.payment ? (
                        <div>
                          <p className="card-text">
                            <strong>Cardholder Name:</strong>{" "}
                            {reservation.payment.cardholder}
                          </p>
                          <p className="card-text">
                            <strong>Bank:</strong> {reservation.payment.bank_name}
                          </p>
                          <p className="card-text">
                            <strong>Card Number:</strong>{" "}
                            {reservation.payment.card_number.slice(-4).padStart(16, "**** **** **** ")}
                          </p>
                          <p className="card-text">
                            <strong>Card Type:</strong> {reservation.payment.card_type}
                          </p>
                        </div>
                      ) : (
                        <p className="card-text">No payment details.</p>
                      )}
                      <button
                        className="btn btn-primary me-2"
                        onClick={() => startEditing(reservation)}
                      >
                        Edit Dates
                      </button>
                      <button
                        className="btn btn-danger"
                        onClick={() => cancelReservation(reservation.id)}
                      >
                        Cancel Reservation
                      </button>
                    </div>
                  )}
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

export default UserDashboard;