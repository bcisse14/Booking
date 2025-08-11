import React, { useState } from 'react';
import axios from 'axios';

export default function User() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [message, setMessage] = useState('');

  const handleSubmit = async e => {
    e.preventDefault();
    
    if (!email || !password) {
      setMessage('Email et mot de passe sont obligatoires');
      return;
    }

    try {
      const response = await axios.post('http://localhost:8000/api/users', {
        email: email.trim(),
        password: password.trim()
      }, {
        headers: {
          'Content-Type': 'application/ld+json',
          'Accept': 'application/ld+json'
        }
      });

      setMessage('✅ Utilisateur créé avec succès !');
      setEmail('');
      setPassword('');
    } catch (err) {
      console.error('Erreur complète:', err.response);
      const errorMsg = err.response?.data?.detail || 
                      err.response?.data?.violations?.map(v => v.message).join(', ') || 
                      'Erreur lors de la création';
      setMessage(`❌ ${errorMsg}`);
    }
  };

  return (
    <div className="p-8 max-w-md mx-auto">
      <h2 className="text-xl font-bold mb-4">Créer un utilisateur</h2>
      <form onSubmit={handleSubmit} className="space-y-4">
        <input
          type="email"
          placeholder="Email"
          required
          pattern="[^@\s]+@[^@\s]+\.[^@\s]+"
          value={email}
          onChange={e => setEmail(e.target.value)}
          className="border p-2 w-full rounded"
        />
        <input
          type="password"
          placeholder="Mot de passe (min. 6 caractères)"
          required
          minLength={6}
          value={password}
          onChange={e => setPassword(e.target.value)}
          className="border p-2 w-full rounded"
        />
        <button 
          type="submit" 
          className="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded w-full"
        >
          Créer
        </button>
      </form>
      {message && (
        <p className={`mt-4 p-2 rounded ${
          message.includes('❌') ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'
        }`}>
          {message}
        </p>
      )}
    </div>
  );
}