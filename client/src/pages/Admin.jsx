import React, { useEffect, useState } from 'react';
import axios from 'axios';
import { useNavigate } from 'react-router-dom';

export default function Admin() {
  const [datetime, setDatetime] = useState('');
  const [message, setMessage] = useState('');
  const [isAdmin, setIsAdmin] = useState(false);
  const navigate = useNavigate();

  useEffect(() => {
    const verifyAdmin = async () => {
      try {
        const response = await axios.get('http://localhost:8000/api/users/me', {
          headers: {
            Authorization: `Bearer ${localStorage.getItem('token')}`
          }
        });
        
        if (!response.data.roles.includes('ROLE_ADMIN')) {
          navigate('/');
        } else {
          setIsAdmin(true);
        }
      } catch (err) {
        navigate('/');
      }
    };

    verifyAdmin();
  }, [navigate]);

  const handleSubmit = async e => {
    e.preventDefault();
    try {
      await axios.post('http://localhost:8000/api/slots', {
        datetime: new Date(datetime).toISOString(),
        reserved: false
      }, {
        headers: {
          'Content-Type': 'application/ld+json',
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        }
      });
      setMessage('Créneau ajouté avec succès');
      setDatetime('');
    } catch (err) {
      setMessage(`Erreur: ${err.response?.data?.message || err.message}`);
    }
  };

  if (!isAdmin) {
    return null; // Ou un loader pendant la vérification
  }

  return (
    <div className="p-8 max-w-md mx-auto">
      <h2>Admin - Ajouter un créneau</h2>
      <form onSubmit={handleSubmit} className="space-y-4">
        <input
          type="datetime-local"
          value={datetime}
          onChange={e => setDatetime(e.target.value)}
          className="border p-2 w-full"
          required
        />
        <button type="submit" className="bg-red-500 text-white px-4 py-2">
          Ajouter
        </button>
      </form>
      <p>{message}</p>
    </div>
  );
}