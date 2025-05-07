import React, { useEffect, useState } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import { Container, Row, Col, Nav, Tab, Button, Card, Spinner, Form, Modal } from "react-bootstrap";
import axios from "axios";
import Pagesat from "./Pagesat";

const Dashboard = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const queryParams = new URLSearchParams(location.search);
  const initialTab = queryParams.get("tab") || "reservations";

  const [reservations, setReservations] = useState([]);
  const [payments, setPayments] = useState([]);
  const [rooms, setRooms] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showEditModal, setShowEditModal] = useState(false);
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [selectedReservation, setSelectedReservation] = useState(null);
  const [editFormData, setEditFormData] = useState({ check_in: "", check_out: "" });
  const [createFormData, setCreateFormData] = useState({
    room_title: "",
    room_price: "",
    check_in: "",
    check_out: "",
    customer_name: "",
    cardholder: "",
    bank_name: "",
    card_number: "",
    cvv: "",
  });
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchData = async () => {
      setLoading(true);
      try {
        const token = localStorage.getItem("token");
        if (!token) {
          console.log("No token found, redirecting to login");
          navigate("/login");
          return;
        }

        const reservationsResponse = await axios.get("/api/reservations", {
          headers: { Authorization: `Bearer ${token}` },
        });
        console.log("Reservations fetched:", reservationsResponse.data);
        setReservations(reservationsResponse.data);

        const roomsResponse = await axios.get("/api/rooms", {
          headers: { Authorization: `Bearer ${token}` },
        });
        console.log("Rooms fetched:", roomsResponse.data);
        setRooms(roomsResponse.data);

        // Simulim për pagesat
        const fetchedPayments = [
          { id: 1, amount: 5000, date: "2025-04-18" },
          { id: 2, amount: 3000, date: "2025-04-19" },
        ];
        setPayments(fetchedPayments);
      } catch (error) {
        console.error("Error fetching data:", error);
        setError(error.response?.data?.message || "Gabim gjatë ngarkimit të të dhënave.");
        if (error.response?.status === 401) {
          console.log("Unauthorized, clearing token");
          localStorage.removeItem("token");
          navigate("/login");
        }
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, [navigate]);

  const handleEdit = (reservation) => {
    console.log("Editing reservation:", reservation);
    setSelectedReservation(reservation);
    setEditFormData({
      check_in: reservation.check_in,
      check_out: reservation.check_out,
    });
    setShowEditModal(true);
  };

  const handleUpdate = async (e) => {
    e.preventDefault();
    try {
      const token = localStorage.getItem("token");
      console.log("Updating reservation with data:", editFormData);
      const response = await axios.put(
        `/api/reservations/${selectedReservation.id}`,
        editFormData,
        {
          headers: { Authorization: `Bearer ${token}` },
        }
      );
      console.log("Update response:", response.data);
      setReservations(
        reservations.map((rez) =>
          rez.id === selectedReservation.id ? response.data.reservation : rez
        )
      );
      setShowEditModal(false);
      alert("Rezervimi u përditësua me sukses!");
    } catch (error) {
      console.error("Error updating reservation:", error);
      setError(error.response?.data?.message || "Gabim gjatë përditësimit të rezervimit.");
    }
  };

  const handleCreate = async (e) => {
    e.preventDefault();
    try {
      const token = localStorage.getItem("token");
      console.log("Creating reservation with data:", createFormData);
      const response = await axios.post("/api/book-room", createFormData, {
        headers: { Authorization: `Bearer ${token}` },
      });
      console.log("Create response:", response.data);
      setReservations([...reservations, response.data.reservation]);
      setShowCreateModal(false);
      setCreateFormData({
        room_title: "",
        room_price: "",
        check_in: "",
        check_out: "",
        customer_name: "",
        cardholder: "",
        bank_name: "",
        card_number: "",
        cvv: "",
      });
      alert("Rezervimi u krijua me sukses!");
    } catch (error) {
      console.error("Error creating reservation:", error);
      setError(error.response?.data?.message || "Gabim gjatë krijimit të rezervimit.");
    }
  };

  const handleDelete = async (id) => {
    if (window.confirm("Jeni i sigurt që doni të fshini këtë rezervim?")) {
      try {
        const token = localStorage.getItem("token");
        console.log("Deleting reservation ID:", id);
        const response = await axios.delete(`/api/reservations/${id}`, {
          headers: { Authorization: `Bearer ${token}` },
        });
        console.log("Delete response:", response.data);
        setReservations(reservations.filter((rez) => rez.id !== id));
        alert("Rezervimi u fshi me sukses!");
      } catch (error) {
        console.error("Error deleting reservation:", error);
        setError(error.response?.data?.message || "Gabim gjatë fshirjes së rezervimit.");
      }
    }
  };

  const handleLogout = async () => {
    try {
      const token = localStorage.getItem("token");
      console.log("Logging out");
      const response = await axios.post("/api/logout", {}, {
        headers: { Authorization: `Bearer ${token}` },
      });
      console.log("Logout response:", response.data);
      localStorage.removeItem("token");
      navigate("/login");
    } catch (error) {
      console.error("Error logging out:", error);
      localStorage.removeItem("token");
      navigate("/login");
    }
  };

  const handleGoToPayments = () => {
    navigate("/payments");
  };

  const handleUpdatePayments = (newPayment) => {
    setPayments((prevPayments) => [...prevPayments, newPayment]);
  };

  return (
    <Container className="mt-5">
      {error && <div className="alert alert-danger">{error}</div>}
      <Row>
        <Col md={3}>
          <h4>Dashboard</h4>
          <Button variant="dark" className="mb-3 w-100" onClick={handleLogout}>
            Log Out
          </Button>
          <Nav variant="pills" className="flex-column">
            <Nav.Item>
              <Nav.Link eventKey="reservations">Rezervimet</Nav.Link>
            </Nav.Item>
            <Nav.Item>
              <Nav.Link eventKey="payments" onClick={handleGoToPayments}>
                Pagesat
              </Nav.Link>
            </Nav.Item>
          </Nav>
        </Col>
        <Col md={9}>
          <Tab.Container defaultActiveKey={initialTab}>
            <Tab.Content>
              <Tab.Pane eventKey="reservations">
                <h5>Rezervimet</h5>
                <Button
                  variant="primary"
                  className="mb-3"
                  onClick={() => {
                    console.log("Opening create modal, current rooms:", rooms);
                    setShowCreateModal(true);
                  }}
                >
                  Shto Rezervim
                </Button>
                {loading ? (
                  <Spinner animation="border" />
                ) : reservations.length > 0 ? (
                  reservations.map((rez) => (
                    <Card className="mb-3" key={rez.id}>
                      <Card.Body>
                        <Card.Title>{rez.room?.title || "Dhoma e panjohur"}</Card.Title>
                        <Card.Text>
                          <strong>Emri i Klientit:</strong> {rez.customer_name} <br />
                          <strong>Check-In:</strong> {rez.check_in} <br />
                          <strong>Check-Out:</strong> {rez.check_out} <br />
                          <strong>Statusi:</strong> {rez.status}
                        </Card.Text>
                        <Button
                          variant="warning"
                          className="me-2"
                          onClick={() => handleEdit(rez)}
                        >
                          Edito
                        </Button>
                        <Button
                          variant="danger"
                          onClick={() => handleDelete(rez.id)}
                        >
                          Fshi
                        </Button>
                      </Card.Body>
                    </Card>
                  ))
                ) : (
                  <p>Nuk ka rezervime.</p>
                )}
              </Tab.Pane>
              <Tab.Pane eventKey="payments">
                <h5>Pagesat</h5>
                <Pagesat payments={payments} onUpdatePayments={handleUpdatePayments} />
              </Tab.Pane>
            </Tab.Content>
          </Tab.Container>
        </Col>
      </Row>

      <Modal show={showEditModal} onHide={() => setShowEditModal(false)}>
        <Modal.Header closeButton>
          <Modal.Title>Edito Rezervimin</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <Form onSubmit={handleUpdate}>
            <Form.Group className="mb-3">
              <Form.Label>Check-In</Form.Label>
              <Form.Control
                type="date"
                value={editFormData.check_in || ""}
                onChange={(e) =>
                  setEditFormData({ ...editFormData, check_in: e.target.value })
                }
                required
              />
            </Form.Group>
            <Form.Group className="mb-3">
              <Form.Label>Check-Out</Form.Label>
              <Form.Control
                type="date"
                value={editFormData.check_out || ""}
                onChange={(e) =>
                  setEditFormData({ ...editFormData, check_out: e.target.value })
                }
                required
              />
            </Form.Group>
            <Button variant="primary" type="submit">
              Përditëso
            </Button>
          </Form>
        </Modal.Body>
      </Modal>

      <Modal show={showCreateModal} onHide={() => setShowCreateModal(false)}>
        <Modal.Header closeButton>
          <Modal.Title>Shto Rezervim</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <Form onSubmit={handleCreate}>
            <Form.Group className="mb-3">
              <Form.Label>Dhoma</Form.Label>
              <Form.Select
                value={createFormData.room_title}
                onChange={(e) => {
                  const selectedRoom = rooms.find((room) => room.title === e.target.value);
                  console.log("Selected room:", selectedRoom);
                  setCreateFormData({
                    ...createFormData,
                    room_title: e.target.value,
                    room_price: selectedRoom ? selectedRoom.price : "",
                  });
                }}
                required
              >
                <option value="">Zgjidh një dhomë</option>
                {rooms.length > 0 ? (
                  rooms.map((room) => (
                    <option key={room.id} value={room.title}>
                      {room.title} ({room.price} ALL)
                    </option>
                  ))
                ) : (
                  <option disabled>No rooms available</option>
                )}
              </Form.Select>
            </Form.Group>
            <Form.Group className="mb-3">
              <Form.Label>Çmimi</Form.Label>
              <Form.Control
                type="number"
                value={createFormData.room_price}
                readOnly
              />
            </Form.Group>
            <Form.Group className="mb-3">
              <Form.Label>Check-In</Form.Label>
              <Form.Control
                type="date"
                value={createFormData.check_in}
                onChange={(e) => {
                  console.log("Check-in changed:", e.target.value);
                  setCreateFormData({ ...createFormData, check_in: e.target.value });
                }}
                required
              />
            </Form.Group>
            <Form.Group className="mb-3">
              <Form.Label>Check-Out</Form.Label>
              <Form.Control
                type="date"
                value={createFormData.check_out}
                onChange={(e) => {
                  console.log("Check-out changed:", e.target.value);
                  setCreateFormData({ ...createFormData, check_out: e.target.value });
                }}
                required
              />
            </Form.Group>
            <Form.Group className="mb-3">
              <Form.Label>Emri i Klientit</Form.Label>
              <Form.Control
                type="text"
                value={createFormData.customer_name}
                onChange={(e) =>
                  setCreateFormData({ ...createFormData, customer_name: e.target.value })
                }
                required
              />
            </Form.Group>
            <Form.Group className="mb-3">
              <Form.Label>Emri i Mbajtësit të Kartës</Form.Label>
              <Form.Control
                type="text"
                value={createFormData.cardholder}
                onChange={(e) =>
                  setCreateFormData({ ...createFormData, cardholder: e.target.value })
                }
                required
              />
            </Form.Group>
            <Form.Group className="mb-3">
              <Form.Label>Emri i Bankës</Form.Label>
              <Form.Control
                type="text"
                value={createFormData.bank_name}
                onChange={(e) =>
                  setCreateFormData({ ...createFormData, bank_name: e.target.value })
                }
                required
              />
            </Form.Group>
            <Form.Group className="mb-3">
              <Form.Label>Numri i Kartës</Form.Label>
              <Form.Control
                type="text"
                value={createFormData.card_number}
                onChange={(e) =>
                  setCreateFormData({ ...createFormData, card_number: e.target.value })
                }
                maxLength="16"
                required
              />
            </Form.Group>
            <Form.Group className="mb-3">
              <Form.Label>CVV</Form.Label>
              <Form.Control
                type="text"
                value={createFormData.cvv}
                onChange={(e) =>
                  setCreateFormData({ ...createFormData, cvv: e.target.value })
                }
                maxLength="3"
                required
              />
            </Form.Group>
            <Button variant="primary" type="submit">
              Rezervo
            </Button>
          </Form>
        </Modal.Body>
      </Modal>
    </Container>
  );
};

export default Dashboard;