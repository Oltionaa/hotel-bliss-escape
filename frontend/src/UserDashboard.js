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
  const navigate = useNavigate();

  // Marrja e rezervimeve të përdoruesit
  useEffect(() => {
    const fetchReservations = async () => {
      try {
        const token = localStorage.getItem("token");
        if (!token) {
          setError("Ju lutem identifikohuni për të parë rezervimet.");
          navigate("/login");
          return;
        }
        const response = await axios.get("http://localhost:8000/api/reservations/user", {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        });
        console.log("API Response:", response.data); // Debug: Shiko çfarë kthen API-ja
        setReservations(response.data.reservations || []);
      } catch (err) {
        console.error("Fetch error:", err.response || err);
        setError("Gabim gjatë marrjes së rezervimeve: " + (err.response?.data?.message || err.message));
      }
    };
    fetchReservations();
  }, [navigate]);

  // Ndryshimi i të dhënave në formën e editimit
  const handleEditChange = (e) => {
    const { name, value } = e.target;
    setEditFormData((prevData) => ({
      ...prevData,
      [name]: value,
    }));
  };

  // Fillimi i editimit të një rezervimi
  const startEditing = (reservation) => {
    setEditReservationId(reservation.id);
    setEditFormData({
      check_in: reservation.check_in,
      check_out: reservation.check_out,
    });
  };

  // Ruajtja e ndryshimeve të editimit
  const saveEdit = async (reservationId) => {
    try {
      const response = await axios.put(
        `http://localhost:8000/api/reservations/${reservationId}`,
        {
          check_in: editFormData.check_in,
          check_out: editFormData.check_out,
        },
        {
          headers: {
            Authorization: `Bearer ${localStorage.getItem("token")}`,
          },
        }
      );
      console.log("Update response:", response.data); // Debug
      setReservations(
        reservations.map((res) =>
          res.id === reservationId ? { ...res, ...response.data.reservation } : res
        )
      );
      setEditReservationId(null);
      setError("");
    } catch (err) {
      console.error("Update error:", err.response || err);
      setError("Gabim gjatë përditësimit të rezervimit: " + (err.response?.data?.message || err.message));
    }
  };

  // Anulimi i një rezervimi
  const cancelReservation = async (reservationId) => {
    if (!window.confirm("Jeni të sigurt që doni të anuloni këtë rezervim?")) return;
    try {
      await axios.delete(`http://localhost:8000/api/reservations/${reservationId}`, {
        headers: {
          Authorization: `Bearer ${localStorage.getItem("token")}`,
        },
      });
      setReservations(reservations.filter((res) => res.id !== reservationId));
      setError("");
    } catch (err) {
      console.error("Delete error:", err.response || err);
      setError("Gabim gjatë anulimit të rezervimit: " + (err.response?.data?.message || err.message));
    }
  };

  return (
    <div className="container mt-5">
      <h2 className="mb-4">Rezervimet e Mia</h2>
      {error && <div className="alert alert-danger mb-4">{error}</div>}
      {reservations.length === 0 ? (
        <p className="text-muted">Nuk keni asnjë rezervim.</p>
      ) : (
        <div className="row">
          {reservations.map((reservation) => (
            <div key={reservation.id} className="col-md-6 mb-4">
              <div className="card shadow-sm">
                <div className="card-body">
                  <h5 className="card-title">
                    Dhoma: {reservation.room?.title || "N/A"}
                  </h5>
                  <p className="card-text">
                    <strong>Emri:</strong> {reservation.customer_name}
                  </p>
                  {editReservationId === reservation.id ? (
                    <div>
                      <div className="mb-3">
                        <label htmlFor="check_in" className="form-label">
                          Data e Hyrjes
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
                          Data e Daljes
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
                      >
                        Ruaj
                      </button>
                      <button
                        className="btn btn-secondary"
                        onClick={() => setEditReservationId(null)}
                      >
                        Anulo Editimin
                      </button>
                    </div>
                  ) : (
                    <div>
                      <p className="card-text">
                        <strong>Data e Hyrjes:</strong> {reservation.check_in}
                      </p>
                      <p className="card-text">
                        <strong>Data e Daljes:</strong> {reservation.check_out}
                      </p>
                      <p className="card-text">
                        <strong>Statusi:</strong> {reservation.status}
                      </p>
                      <h6>Detajet e Pagesës</h6>
                      {reservation.payment ? (
                        <div>
                          <p className="card-text">
                            <strong>Emri i Mbajtësit:</strong>{" "}
                            {reservation.payment.cardholder}
                          </p>
                          <p className="card-text">
                            <strong>Banka:</strong> {reservation.payment.bank_name}
                          </p>
                          <p className="card-text">
                            <strong>Numri i Kartës:</strong>{" "}
                            {reservation.payment.card_number.slice(-4).padStart(16, "**** **** **** ")}
                          </p>
                          <p className="card-text">
                            <strong>Tipi i Kartës:</strong> {reservation.payment.card_type}
                          </p>
                        </div>
                      ) : (
                        <p className="card-text">Nuk ka detaje pagese.</p>
                      )}
                      <button
                        className="btn btn-primary me-2"
                        onClick={() => startEditing(reservation)}
                      >
                        Edito Datat
                      </button>
                      <button
                        className="btn btn-danger"
                        onClick={() => cancelReservation(reservation.id)}
                      >
                        Anulo Rezervimin
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