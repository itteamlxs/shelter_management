<?php
// app/controllers/RefugiosController.php

class RefugiosController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAllRefugios() {
        $stmt = $this->pdo->query('SELECT * FROM Refugios');
        return $stmt->fetchAll();
    }

    public function getRefugioById($id) {
        $stmt = $this->pdo->prepare('SELECT * FROM Refugios WHERE refugio_id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function createRefugio($data) {
        $stmt = $this->pdo->prepare('INSERT INTO Refugios (nombre_refugio, ubicacion, lat, lng, fecha_apertura, capacidad_maxima) VALUES (:nombre, :ubicacion, :lat, :lng, :fecha_apertura, :capacidad_maxima)');
        return $stmt->execute($data);
    }

    public function updateRefugio($id, $data) {
        $stmt = $this->pdo->prepare('UPDATE Refugios SET nombre_refugio = :nombre, ubicacion = :ubicacion, lat = :lat, lng = :lng, fecha_apertura = :fecha_apertura, capacidad_maxima = :capacidad_maxima WHERE refugio_id = :id');
        return $stmt->execute(array_merge($data, ['id' => $id]));
    }

    public function deleteRefugio($id) {
        $stmt = $this->pdo->prepare('DELETE FROM Refugios WHERE refugio_id = :id');
        return $stmt->execute(['id' => $id]);
    }
}