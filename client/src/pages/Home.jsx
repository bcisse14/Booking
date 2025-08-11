import React, { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import axios from 'axios';

export default function Home() {
  const [isAdmin, setIsAdmin] = useState(false);
  const navigate = useNavigate();

  useEffect(() => {
    const checkAdminStatus = async () => {
      try {
        const response = await axios.get('http://localhost:8000/api/users/me', {
          headers: {
            Authorization: `Bearer ${localStorage.getItem('token')}`
          }
        });
        setIsAdmin(response.data.roles.includes('ROLE_ADMIN'));
      } catch (err) {
        console.error('Erreur de vérification admin:', err);
      }
    };

    checkAdminStatus();
  }, []);

  return (
    <div className="p-8 max-w-md mx-auto space-y-4">
      <h1>Bienvenue</h1>
      <nav className="space-y-2">
        <Link to="/user" className="text-blue-600 underline">Créer utilisateur</Link>
        <br />
        <Link to="/booking" className="text-green-600 underline">Prendre rendez-vous</Link>
        <br />
        {isAdmin && (
          <Link to="/admin" className="text-red-600 underline">Admin - Ajouter créneau</Link>
        )}
      </nav>
    </div>
  );
}