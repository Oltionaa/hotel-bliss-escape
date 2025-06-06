import React, { useState, useEffect, useCallback } from 'react';
import axios from 'axios';
import { Modal, Button, Form, Alert } from 'react-bootstrap';
import { useLocation, useNavigate } from 'react-router-dom';

function AdminScheduleManager() {
    const [receptionistSchedules, setReceptionistSchedules] = useState([]);
    const [cleanerSchedules, setCleanerSchedules] = useState([]);
    const [users, setUsers] = useState([]);
    const [loadingReceptionist, setLoadingReceptionist] = useState(true);
    const [loadingCleaner, setLoadingCleaner] = useState(true);
    const [loadingUsers, setLoadingUsers] = useState(true);
    const [error, setError] = useState(null);
    const [message, setMessage] = useState(null);
    const [showModal, setShowModal] = useState(false);
    const [isEditing, setIsEditing] = useState(false);
    const [currentSchedule, setCurrentSchedule] = useState(null);
    const [formData, setFormData] = useState({
        user_id: '',
        work_date: '',
        shift_start: '',
        shift_end: '',
        status: 'Planned',
        type: 'receptionist',
    });

    const location = useLocation();
    const navigate = useNavigate(); 
    const authToken = location.state?.authToken || localStorage.getItem('token');

    const formatTimeForInput = (dateTimeString) => {
        if (!dateTimeString) return '';
        if (dateTimeString.includes(' ')) {
            return dateTimeString.substring(11, 16);
        }
        if (dateTimeString.includes('T')) {
            return dateTimeString.split('T')[1].substring(0, 5);
        }
        return dateTimeString.substring(0, 5);
    };

    // handleFetchError correctly includes navigate because it uses it.
    const handleFetchError = useCallback((err) => {
        if (err.response?.status === 401) {
            setError('Sesioni juaj ka skaduar. Ju lutem kyçuni sërish.');
            localStorage.removeItem('token');
            localStorage.removeItem('user_id');
            localStorage.removeItem('userType');
            navigate('/login', { replace: true });
        } else if (err.response?.status === 403) {
            setError('Nuk keni leje për të parë këto të dhëna.');
        } else {
            setError(err.response?.data?.message || err.response?.data?.error || 'Gabim gjatë ngarkimit të të dhënave.');
        }
    }, [navigate]); // navigate dependency is correct here

    // Removed navigate from here.
    const fetchReceptionistSchedules = useCallback(async () => {
        setLoadingReceptionist(true);
        try {
            await axios.get('http://localhost:8000/sanctum/csrf-cookie');
            const res = await axios.get('http://localhost:8000/api/receptionist/schedules/all', {
                headers: { Authorization: `Bearer ${authToken}` },
            });
            setReceptionistSchedules(Array.isArray(res.data) ? res.data : []);
        } catch (err) {
            handleFetchError(err);
        } finally {
            setLoadingReceptionist(false);
        }
    }, [authToken, handleFetchError]); 

    const fetchCleanerSchedules = useCallback(async () => {
        setLoadingCleaner(true);
        try {
            const res = await axios.get('http://localhost:8000/api/cleaner/schedules/my', {
                headers: { Authorization: `Bearer ${authToken}` },
            });
            setCleanerSchedules(Array.isArray(res.data) ? res.data : []);
        } catch (err) {
            handleFetchError(err);
        } finally {
            setLoadingCleaner(false);
        }
    }, [authToken, handleFetchError]); // Corrected: removed navigate
    const fetchUsers = useCallback(async () => {
        setLoadingUsers(true);
        try {
            const res = await axios.get('http://localhost:8000/api/admin/users', {
                headers: { Authorization: `Bearer ${authToken}` },
            });
            const usersData = Array.isArray(res.data) ? res.data : [];
            setUsers(usersData.filter(user => ['receptionist', 'cleaner'].includes(user.role)));
        } catch (err) {
            handleFetchError(err);
        } finally {
            setLoadingUsers(false);
        }
    }, [authToken, handleFetchError]); 


    useEffect(() => {
        if (!authToken) {
            setError('Ju lutem kyçuni për të vazhduar.');
            localStorage.removeItem('token');
            localStorage.removeItem('user_id');
            localStorage.removeItem('userType');
            navigate('/login', { replace: true });
            return;
        }

        fetchReceptionistSchedules();
        fetchCleanerSchedules();
        fetchUsers();
    }, [authToken, navigate, fetchReceptionistSchedules, fetchCleanerSchedules, fetchUsers]);


    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setFormData({ ...formData, [name]: value });
    };

    const openModal = (schedule = null, type = 'receptionist') => {
        if (schedule) {
            setIsEditing(true);
            setCurrentSchedule(schedule);
            setFormData({
                user_id: String(schedule.receptionist_id || schedule.cleaner_id || ''),
                work_date: schedule.work_date ? schedule.work_date.split('T')[0] : '',
                shift_start: schedule.shift_start ? formatTimeForInput(schedule.shift_start) : '',
                shift_end: schedule.shift_end ? formatTimeForInput(schedule.shift_end) : '',
                status: schedule.status || 'Planned',
                type,
            });
        } else {
            setIsEditing(false);
            setCurrentSchedule(null);
            setFormData({
                user_id: '',
                work_date: '',
                shift_start: '',
                shift_end: '',
                status: 'Planned',
                type,
            });
        }
        setShowModal(true);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError(null);
        setMessage(null);

        if (isEditing && !currentSchedule?.id) {
            setError('Orari i zgjedhur është i pavlefshëm.');
            return;
        }
        if (!isEditing && !formData.user_id) {
            setError('Ju lutem zgjidhni një përdorues.');
            return;
        }

        const url = formData.type === 'receptionist'
            ? isEditing
                ? `http://localhost:8000/api/admin/receptionist/schedules/${currentSchedule?.id}`
                : `http://localhost:8000/api/admin/receptionist/schedules`
            : isEditing
                ? `http://localhost:8000/api/admin/cleaner/schedules/${currentSchedule?.id}`
                : `http://localhost:8000/api/admin/cleaner/schedules`;

        const data = {
            [formData.type === 'receptionist' ? 'receptionist_id' : 'cleaner_id']: formData.user_id,
            work_date: formData.work_date,
            shift_start: formData.shift_start,
            shift_end: formData.shift_end,
            status: formData.status,
        };

        try {
            const response = await axios({
                method: isEditing ? 'PUT' : 'POST',
                url,
                data,
                headers: { Authorization: `Bearer ${authToken}`, 'Content-Type': 'application/json' },
            });

            if (isEditing) {
                if (formData.type === 'receptionist') {
                    setReceptionistSchedules(prev =>
                        prev.map(s => (s.id === currentSchedule.id ? response.data.schedule : s))
                    );
                } else {
                    setCleanerSchedules(prev =>
                        prev.map(s => (s.id === currentSchedule.id ? response.data.schedule : s))
                    );
                }
            } else {
                if (formData.type === 'receptionist') {
                    setReceptionistSchedules(prev => [...prev, response.data.schedule]);
                } else {
                    setCleanerSchedules(prev => [...prev, response.data.schedule]);
                }
            }
            setMessage(response.data.message || 'Orari u ruajt me sukses.');
            setShowModal(false);
            if (formData.type === 'receptionist') {
                fetchReceptionistSchedules();
            } else {
                fetchCleanerSchedules();
            }
        } catch (err) {
            if (err.response?.status === 401) {
                setError('Sesioni juaj ka skaduar. Ju lutem kyçuni sërish.');
                localStorage.removeItem('token');
                localStorage.removeItem('user_id');
                localStorage.removeItem('userType');
                navigate('/login', { replace: true });
            } else if (err.response?.status === 403) {
                setError('Nuk keni leje për të përditësuar orarin.');
            } else if (err.response?.status === 404) {
                setError('Orari nuk u gjet. Ju lutem kontrolloni nëse orari ekziston.');
            } else if (err.response?.status === 422) {
                setError(err.response?.data?.errors ? JSON.stringify(err.response.data.errors) : 'Të dhënat e dhëna janë të pavlefshme. Kontrolloni fushat.');
            } else if (err.response?.status === 409) {
                setError(err.response?.data?.message || 'Orari për këtë përdorues dhe datë ekziston tashmë.');
            }
            else {
                setError(err.response?.data?.message || err.response?.data?.error || 'Gabim gjatë ruajtjes së orarit.');
            }
        }
    };

    const formatDate = (dateString) => {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleDateString('sq-AL', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
        });
    };
    const formatTime = (timeString) => {
        if (!timeString) return 'N/A';
        if (timeString.includes('T')) {
            return timeString.split('T')[1].substring(0, 5);
        }
        if (timeString.includes(' ')) {
            return timeString.substring(11, 16);
        }
        return timeString.substring(0, 5);
    };

    if (loadingReceptionist && loadingCleaner && loadingUsers) return <p>Duke ngarkuar...</p>;

    return (
        <div className="container mt-4">
            <h2>Menaxhimi i Orareve</h2>
            {error && <Alert variant="danger">{error}</Alert>}
            {message && <Alert variant="success">{message}</Alert>}

            <div className="mb-3">
                <Button variant="primary" onClick={() => openModal(null, 'receptionist')}>
                    Krijo Orar për Recepsionist
                </Button>
                <Button variant="primary" className="ms-2" onClick={() => openModal(null, 'cleaner')}>
                    Krijo Orar për Pastrues
                </Button>
            </div>

            <h4>Oraret e Recepsionistëve</h4>
            <div className="table-responsive">
                <table className="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Recepsionisti</th>
                            <th>Data</th>
                            <th>Fillimi</th>
                            <th>Fundi</th>
                            <th>Statusi</th>
                            <th>Veprimet</th>
                        </tr>
                    </thead>
                    <tbody>
                        {loadingReceptionist ? (
                            <tr>
                                <td colSpan={6}>Duke ngarkuar oraret e recepsionistëve...</td>
                            </tr>
                        ) : receptionistSchedules.length === 0 ? (
                            <tr>
                                <td colSpan={6}>Nuk ka orare për recepsionistë.</td>
                            </tr>
                        ) : (
                            receptionistSchedules.map(schedule => (
                                <tr key={schedule.id}>
                                    <td>{schedule.receptionist?.name || 'N/A'}</td>
                                    <td>{formatDate(schedule.work_date)}</td>
                                    <td>{formatTime(schedule.shift_start)}</td>
                                    <td>{formatTime(schedule.shift_end)}</td>
                                    <td>
                                        <span
                                            className={`badge ${
                                                schedule.status === 'Planned'
                                                    ? 'bg-info text-dark'
                                                    : schedule.status === 'Completed'
                                                    ? 'bg-success'
                                                    : 'bg-danger'
                                            }`}
                                        >
                                            {schedule.status}
                                        </span>
                                    </td>
                                    <td>
                                        <Button
                                            variant="warning"
                                            size="sm"
                                            onClick={() => openModal(schedule, 'receptionist')}
                                        >
                                            Modifiko
                                        </Button>
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>

            <h4>Oraret e Pastruesve</h4>
            <div className="table-responsive">
                <table className="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Pastruesi</th>
                            <th>Data</th>
                            <th>Fillimi</th>
                            <th>Fundi</th>
                            <th>Statusi</th>
                            <th>Veprimet</th>
                        </tr>
                    </thead>
                    <tbody>
                        {loadingCleaner ? (
                            <tr>
                                <td colSpan={6}>Duke ngarkuar oraret e pastruesve...</td>
                            </tr>
                        ) : cleanerSchedules.length === 0 ? (
                            <tr>
                                <td colSpan={6}>Nuk ka orare për pastrues.</td>
                            </tr>
                        ) : (
                            cleanerSchedules.map(schedule => (
                                <tr key={schedule.id}>
                                    <td>{schedule.cleaner?.name || 'N/A'}</td>
                                    <td>{formatDate(schedule.work_date)}</td>
                                    <td>{formatTime(schedule.shift_start)}</td>
                                    <td>{formatTime(schedule.shift_end)}</td>
                                    <td>
                                        <span
                                            className={`badge ${
                                                schedule.status === 'Planned'
                                                    ? 'bg-info text-dark'
                                                    : schedule.status === 'Completed'
                                                    ? 'bg-success'
                                                    : 'bg-danger'
                                            }`}
                                        >
                                            {schedule.status}
                                        </span>
                                    </td>
                                    <td>
                                        <Button
                                            variant="warning"
                                            size="sm"
                                            onClick={() => openModal(schedule, 'cleaner')}
                                        >
                                            Modifiko
                                        </Button>
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>

            <Modal show={showModal} onHide={() => setShowModal(false)}>
                <Modal.Header closeButton>
                    <Modal.Title>{isEditing ? 'Modifiko Orarin' : 'Krijo Orar të Ri'}</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Form onSubmit={handleSubmit}>
                        <Form.Group className="mb-3">
                            <Form.Label>Lloji</Form.Label>
                            <Form.Select
                                name="type"
                                value={formData.type}
                                onChange={handleInputChange}
                                disabled={isEditing}
                            >
                                <option value="receptionist">Recepsionist</option>
                                <option value="cleaner">Pastrues</option>
                            </Form.Select>
                        </Form.Group>

                        {!isEditing && (
                            <Form.Group className="mb-3">
                                <Form.Label>{formData.type === 'receptionist' ? 'Recepsionisti' : 'Pastruesi'}</Form.Label>
                                <Form.Select
                                    name="user_id"
                                    value={formData.user_id}
                                    onChange={handleInputChange}
                                    required
                                >
                                    <option value="">Zgjidhni një {formData.type === 'receptionist' ? 'recepsionist' : 'pastrues'}</option>
                                    {loadingUsers ? (
                                        <option disabled>Duke ngarkuar përdoruesit...</option>
                                    ) : users
                                        .filter(user => user.role === formData.type)
                                        .map(user => (
                                            <option key={user.id} value={user.id}>
                                                {user.name}
                                            </option>
                                        ))}
                                </Form.Select>
                            </Form.Group>
                        )}

                        <Form.Group className="mb-3">
                            <Form.Label>Data</Form.Label>
                            <Form.Control
                                type="date"
                                name="work_date"
                                value={formData.work_date}
                                onChange={handleInputChange}
                                required
                            />
                        </Form.Group>
                        <Form.Group className="mb-3">
                            <Form.Label>Fillimi i Turnit</Form.Label>
                            <Form.Control
                                type="time"
                                name="shift_start"
                                value={formData.shift_start}
                                onChange={handleInputChange}
                                required
                            />
                        </Form.Group>
                        <Form.Group className="mb-3">
                            <Form.Label>Fundi i Turnit</Form.Label>
                            <Form.Control
                                type="time"
                                name="shift_end"
                                value={formData.shift_end}
                                onChange={handleInputChange}
                                required
                            />
                        </Form.Group>
                        <Form.Group className="mb-3">
                            <Form.Label>Statusi</Form.Label>
                            <Form.Select name="status" value={formData.status} onChange={handleInputChange}>
                                <option value="Planned">Planifikuar</option>
                                <option value="Completed">Përfunduar</option>
                                <option value="Canceled">Anuluar</option>
                            </Form.Select>
                        </Form.Group>
                        <Button variant="primary" type="submit">
                            {isEditing ? 'Përditëso' : 'Krijo'}
                        </Button>
                    </Form>
                </Modal.Body>
            </Modal>
        </div>
    );
}

export default AdminScheduleManager;