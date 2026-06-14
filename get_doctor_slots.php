<?php
   session_start();
   include 'db.php';
   
   if(!isset($_SESSION['parent_id'])){
       echo json_encode(['slots' => []]);
       exit();
   }
   
   $doctor_id = intval($_GET['doctor_id']);
   
   $query = mysqli_query($conn, "SELECT ds.*, 
       DATE_FORMAT(ds.slot_date, '%a, %b %e') as date_display,
       DATE_FORMAT(ds.slot_time, '%h:%i %p') as time_display
       FROM doctor_slots ds
       WHERE ds.doctor_id = '$doctor_id' 
       AND ds.slot_date >= CURDATE() 
       AND ds.is_booked = 0
       ORDER BY ds.slot_date ASC, ds.slot_time ASC
       LIMIT 30");
   
   $slots = [];
   while($row = mysqli_fetch_assoc($query)){
       $slots[] = [
           'id' => $row['id'],
           'date' => $row['slot_date'],
           'date_display' => $row['date_display'],
           'time' => $row['time_display'],
           'time_24' => $row['slot_time']
       ];
   }
   
   echo json_encode(['slots' => $slots]);
   ?>