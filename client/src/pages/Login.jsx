import React, { useState } from 'react';
import axios from 'axios';
import { Link } from 'react-router-dom';

export default function User() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [message, setMessage] = useState('');
  const [loading, setLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);

  const validateForm = () => {
    if (!email || !password || !confirmPassword) {
      setMessage('❌ Tous les champs sont obligatoires');
      return false;
    }

    if (password !== confirmPassword) {
      setMessage('❌ Les mots de passe ne correspondent pas');
      return false;
    }

    if (password.length < 6) {
      setMessage('❌ Le mot de passe doit contenir au moins 6 caractères');
      return false;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      setMessage('❌ Format d\'email invalide');
      return false;
    }

    return true;
  };

  const handleSubmit = async e => {
    e.preventDefault();
    
    if (!validateForm()) return;

    setLoading(true);
    setMessage('');

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

      setMessage('✅ Utilisateur créé avec succès ! Vous pouvez maintenant vous connecter.');
      setEmail('');
      setPassword('');
      setConfirmPassword('');
    } catch (err) {
      console.error('Erreur complète:', err.response);
      
      if (err.response?.status === 422) {
        const violations = err.response.data?.violations || [];
        if (violations.length > 0) {
          setMessage(`❌ ${violations.map(v => v.message).join(', ')}`);
        } else {
          setMessage('❌ Données invalides');
        }
      } else if (err.response?.status === 409) {
        setMessage('❌ Un utilisateur avec cet email existe déjà');
      } else {
        const errorMsg = err.response?.data?.detail || 
                        err.response?.data?.message || 
                        'Erreur lors de la création';
        setMessage(`❌ ${errorMsg}`);
      }
    } finally {
      setLoading(false);
    }
  };

  const getPasswordStrength = () => {
    if (password.length === 0) return { strength: 0, text: '', color: '' };
    if (password.length < 6) return { strength: 1, text: 'Faible', color: 'text-red-500' };
    if (password.length < 8) return { strength: 2, text: 'Moyen', color: 'text-yellow-500' };
    if (password.match(/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/)) {
      return { strength: 4, text: 'Très fort', color: 'text-green-600' };
    }
    return { strength: 3, text: 'Fort', color: 'text-green-500' };
  };

  const passwordStrength = getPasswordStrength();

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50">
      {/* Header */}
      <header className="bg-white/80 backdrop-blur-md shadow-lg border-b border-blue-100">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            <Link to="/" className="flex items-center space-x-2 text-blue-600 hover:text-blue-700">
              <span className="text-xl">←</span>
              <span className="font-medium">Retour à l'accueil</span>
            </Link>
            <h1 className="text-2xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
              👤 Créer un compte
            </h1>
            <div></div>
          </div>
        </div>
      </header>

      <div className="flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div className="max-w-md w-full">
          {/* Card principale */}
          <div className="bg-white rounded-2xl shadow-2xl p-8 border border-gray-100">
            <div className="text-center mb-8">
              <div className="w-16 h-16 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <span className="text-3xl text-white">👤</span>
              </div>
              <h2 className="text-3xl font-bold text-gray-900">Créer votre compte</h2>
              <p className="text-gray-600 mt-2">Rejoignez-nous pour réserver vos rendez-vous</p>
            </div>

            <form onSubmit={handleSubmit} className="space-y-6">
              {/* Email */}
              <div>
                <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-2">
                  Adresse email
                </label>
                <div className="relative">
                  <input
                    id="email"
                    type="email"
                    placeholder="votre@email.com"
                    required
                    value={email}
                    onChange={e => setEmail(e.target.value)}
                    className="w-full px-4 py-3 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                  />
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span className="text-gray-400">📧</span>
                  </div>
                </div>
              </div>

              {/* Mot de passe */}
              <div>
                <label htmlFor="password" className="block text-sm font-medium text-gray-700 mb-2">
                  Mot de passe
                </label>
                <div className="relative">
                  <input
                    id="password"
                    type={showPassword ? 'text' : 'password'}
                    placeholder="Minimum 6 caractères"
                    required
                    minLength={6}
                    value={password}
                    onChange={e => setPassword(e.target.value)}
                    className="w-full px-4 py-3 pl-10 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                  />
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span className="text-gray-400">🔒</span>
                  </div>
                  <button
                    type="button"
                    onClick={() => setShowPassword(!showPassword)}
                    className="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                  >
                    <span>{showPassword ? '👁️' : '👁️‍🗨️'}</span>
                  </button>
                </div>

                {/* Indicateur de force du mot de passe */}
                {password && (
                  <div className="mt-2">
                    <div className="flex items-center space-x-2">
                      <div className="flex-1 bg-gray-200 rounded-full h-2">
                        <div 
                          className={`h-2 rounded-full transition-all duration-300 ${
                            passwordStrength.strength === 1 ? 'w-1/4 bg-red-500' :
                            passwordStrength.strength === 2 ? 'w-2/4 bg-yellow-500' :
                            passwordStrength.strength === 3 ? 'w-3/4 bg-green-500' :
                            passwordStrength.strength === 4 ? 'w-full bg-green-600' : 'w-0'
                          }`}
                        ></div>
                      </div>
                      <span className={`text-xs font-medium ${passwordStrength.color}`}>
                        {passwordStrength.text}
                      </span>
                    </div>
                  </div>
                )}
              </div>

              {/* Confirmation mot de passe */}
              <div>
                <label htmlFor="confirmPassword" className="block text-sm font-medium text-gray-700 mb-2">
                  Confirmer le mot de passe
                </label>
                <div className="relative">
                  <input
                    id="confirmPassword"
                    type="password"
                    placeholder="Confirmez votre mot de passe"
                    required
                    value={confirmPassword}
                    onChange={e => setConfirmPassword(e.target.value)}
                    className={`w-full px-4 py-3 pl-10 border rounded-lg focus:ring-2 focus:ring-blue-500 transition-colors ${
                      confirmPassword && password !== confirmPassword 
                        ? 'border-red-300 focus:border-red-500' 
                        : 'border-gray-300 focus:border-blue-500'
                    }`}
                  />
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span className="text-gray-400">🔒</span>
                  </div>
                  {confirmPassword && (
                    <div className="absolute inset-y-0 right-0 pr-3 flex items-center">
                      <span className={password === confirmPassword ? 'text-green-500' : 'text-red-500'}>
                        {password === confirmPassword ? '✅' : '❌'}
                      </span>
                    </div>
                  )}
                </div>
              </div>

              {/* Bouton de soumission */}
              <button
                type="submit"
                disabled={loading}
                className={`
                  w-full py-4 px-6 rounded-lg font-semibold text-lg transition-all duration-200 transform
                  ${loading 
                    ? 'bg-gray-400 cursor-not-allowed' 
                    : 'bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white hover:scale-105 shadow-lg hover:shadow-xl'
                  }
                `}
              >
                {loading ? (
                  <div className="flex items-center justify-center space-x-2">
                    <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
                    <span>Création en cours...</span>
                  </div>
                ) : (
                  'Créer mon compte'
                )}
              </button>
            </form>

            {/* Message */}
            {message && (
              <div className={`
                mt-6 p-4 rounded-lg text-center font-medium transition-all duration-300
                ${message.includes('❌') 
                  ? 'bg-red-50 text-red-700 border border-red-200' 
                  : 'bg-green-50 text-green-700 border border-green-200'
                }
              `}>
                {message}
              </div>
            )}

            {/* Lien vers connexion */}
            <div className="mt-8 text-center">
              <p className="text-gray-600">
                Déjà un compte ?{' '}
                <Link to="/login" className="text-blue-600 hover:text-blue-700 font-medium">
                  Se connecter
                </Link>
              </p>
            </div>
          </div>

          {/* Conseils de sécurité */}
          <div className="mt-8 bg-white/60 backdrop-blur-sm rounded-xl p-6 border border-gray-100">
            <h3 className="text-lg font-semibold text-gray-900 mb-3">💡 Conseils pour un mot de passe sécurisé</h3>
            <ul className="text-sm text-gray-600 space-y-1">
              <li>• Au moins 8 caractères</li>
              <li>• Mélange de majuscules et minuscules</li>
              <li>• Inclure des chiffres</li>
              <li>• Éviter les informations personnelles</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  );
}