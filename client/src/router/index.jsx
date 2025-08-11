import React from "react";
import { BrowserRouter as Router, Route, Routes } from "react-router-dom";

import { Home } from "../pages/Home";
import { Booking } from "../pages/Booking";
import { Confirmation } from "../pages/Confirmation";
import { Admin } from "../pages/Admin"; 
import { User } from "../pages/User";
import { Login } from "../pages/Login";


export default function AppRouter() {
  return (
    <Router>
      <Routes>
        <Route path="/" element={<Home />} />
        <Route path="/booking" element={<Booking />} />
        <Route path="/confirmation" element={<Confirmation />} />
        <Route path="/user" element={<User />} />
        <Route path="/login" element={<Login />} />
        <Route path="/admin" element={<Admin />} />
      </Routes>
    </Router>
  );
}
