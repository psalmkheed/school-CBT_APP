<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

$nigeriaData = [
      ["state" => "Abia", "capital" => "Umuahia"],
      ["state" => "Adamawa", "capital" => "Yola"],
      ["state" => "Akwa Ibom", "capital" => "Uyo"],
      ["state" => "Anambra", "capital" => "Awka"],
      ["state" => "Bauchi", "capital" => "Bauchi"],
      ["state" => "Bayelsa", "capital" => "Yenagoa"],
      ["state" => "Benue", "capital" => "Makurdi"],
      ["state" => "Borno", "capital" => "Maiduguri"],
      ["state" => "Cross River", "capital" => "Calabar"],
      ["state" => "Delta", "capital" => "Asaba"],
      ["state" => "Ebonyi", "capital" => "Abakaliki"],
      ["state" => "Edo", "capital" => "Benin City"],
      ["state" => "Ekiti", "capital" => "Ado-Ekiti"],
      ["state" => "Enugu", "capital" => "Enugu"],
      ["state" => "Federal Capital Territory", "capital" => "Abuja"],
      ["state" => "Gombe", "capital" => "Gombe"],
      ["state" => "Imo", "capital" => "Owerri"],
      ["state" => "Jigawa", "capital" => "Dutse"],
      ["state" => "Kaduna", "capital" => "Kaduna"],
      ["state" => "Kano", "capital" => "Kano"],
      ["state" => "Katsina", "capital" => "Katsina"],
      ["state" => "Kebbi", "capital" => "Birnin Kebbi"],
      ["state" => "Kogi", "capital" => "Lokoja"],
      ["state" => "Kwara", "capital" => "Ilorin"],
      ["state" => "Lagos", "capital" => "Ikeja"],
      ["state" => "Nasarawa", "capital" => "Lafia"],
      ["state" => "Niger", "capital" => "Minna"],
      ["state" => "Ogun", "capital" => "Abeokuta"],
      ["state" => "Ondo", "capital" => "Akure"],
      ["state" => "Osun", "capital" => "Osogbo"],
      ["state" => "Oyo", "capital" => "Ibadan"],
      ["state" => "Plateau", "capital" => "Jos"],
      ["state" => "Rivers", "capital" => "Port Harcourt"],
      ["state" => "Sokoto", "capital" => "Sokoto"],
      ["state" => "Taraba", "capital" => "Jalingo"],
      ["state" => "Yobe", "capital" => "Damaturu"],
      ["state" => "Zamfara", "capital" => "Gusau"]
];

// Check if a specific state is requested via ?name=StateName
if (isset($_GET['name'])) {
      $search = strtolower($_GET['name']);
      $result = array_filter($nigeriaData, function ($item) use ($search) {
            return strtolower($item['state']) === $search;
      });

      if (!empty($result)) {
            echo json_encode(array_values($result)[0]);
      } else {
            http_response_code(404);
            echo json_encode(["error" => "State not found"]);
      }
} else {
      // Return all states and capitals
      echo json_encode($nigeriaData);
}
?>