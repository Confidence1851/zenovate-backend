<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Google Maps Car Animation with Autocomplete</title>
  <!-- Bootstrap 4 CSS -->
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
  <style>
    #map {
      height: 75vh;
      width: 100%;
    }
  </style>
</head>
<body>
  <div class="container mt-3">
    <div id="map" class="mb-3"></div>
    <div id="controls" class="form-row justify-content-center">
      <div class="col-md-6">
        <input type="text" id="destination" class="form-control" placeholder="Enter destination address">
      </div>
      <div class="col-auto">
        <button id="startButton" class="btn btn-primary">Start Journey</button>
      </div>
    </div>
  </div>

  <!-- Google Maps API Script with Places Library -->
  <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBa8GZX77tCajPP-8QIicjfame8sBHjVEo&libraries&libraries=places"></script>
  <!-- Optional Bootstrap 4 JavaScript -->
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

  <script>
    let map, marker, directionsService, directionsRenderer, stepIndex = 0;
    let steps = [];
    let autocomplete;

    function initMap() {
      // Initialize map centered on user location
      navigator.geolocation.getCurrentPosition(position => {
        const userLocation = {
          lat: position.coords.latitude,
          lng: position.coords.longitude
        };

        // Render map centered at user's current location
        map = new google.maps.Map(document.getElementById("map"), {
          zoom: 14,
          center: userLocation
        });

        // Place a marker at the user's current location
        marker = new google.maps.Marker({
          position: userLocation,
          map: map,
          icon: {
            url: "https://img.icons8.com/emoji/48/000000/car-emoji.png",
            scaledSize: new google.maps.Size(30, 30)
          }
        });

        directionsService = new google.maps.DirectionsService();
        directionsRenderer = new google.maps.DirectionsRenderer({ map: map });

        // Initialize autocomplete for destination input
        autocomplete = new google.maps.places.Autocomplete(
          document.getElementById("destination"),
          { types: ["geocode"] }
        );
      });
    }

    // Get route and animate car
    function startJourney() {
      const destination = document.getElementById("destination").value;
      if (!destination) return alert("Please enter a destination.");

      // Get user's current location again
      navigator.geolocation.getCurrentPosition(position => {
        const start = {
          lat: position.coords.latitude,
          lng: position.coords.longitude
        };

        // Calculate directions
        directionsService.route({
          origin: start,
          destination: destination,
          travelMode: google.maps.TravelMode.DRIVING
        }, (response, status) => {
          if (status === google.maps.DirectionsStatus.OK) {
            directionsRenderer.setDirections(response);

            // Extract steps from response
            steps = response.routes[0].legs[0].steps.map(step => step.end_location);
            stepIndex = 0; // Reset step index for each new journey
            animateCar();
          } else {
            alert("Directions request failed due to " + status);
          }
        });
      });
    }

    // Animate car every 1 second to follow route
    function animateCar() {
      if (stepIndex >= steps.length) return; // Stop when we reach the end

      marker.setPosition(steps[stepIndex]); // Move car to next step location
      map.panTo(steps[stepIndex]); // Center map on new position
      stepIndex++;
      setTimeout(animateCar, 1000); // Move every 1 second
    }

    document.getElementById("startButton").onclick = startJourney;

    // Load the map
    window.onload = initMap;
  </script>
</body>
</html>
