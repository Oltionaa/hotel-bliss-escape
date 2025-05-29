import React, { useState, useEffect } from 'react';
import axios from 'axios';

const Users = () => {
    const [users, setUsers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    const fetchUsers = async () => {
        try {
            const response = await axios.get('http://127.0.0.1:8000/api/users');
            setUsers(response.data);
            setLoading(false); 
        } catch (error) {
            setError('Ka ndodhur një gabim gjatë marrëdhënies së përdoruesve');
            setLoading(false); 
        }
    };

    useEffect(() => {
        fetchUsers(); 
    }, []);

    if (loading) {
        return <div>Po ngarkohet...</div>; 
    }

    if (error) {
        return <div>{error}</div>; 
    }

    return (
        <div>
            <h2>Përdoruesit</h2>
            <ul>
                {users.map((user) => (
                    <li key={user.id}>
                        {user.first_name} {user.last_name} - {user.email}
                    </li>
                ))}
            </ul>
        </div>
    );
};

export default Users;