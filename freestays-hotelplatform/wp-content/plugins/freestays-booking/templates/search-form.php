<form id="hotel-search-form" method="GET" action="">
    <div class="form-group">
        <label for="checkin">Check-in Date:</label>
        <input type="date" id="checkin" name="checkin" required>
    </div>
    <div class="form-group">
        <label for="checkout">Check-out Date:</label>
        <input type="date" id="checkout" name="checkout" required>
    </div>
    <div class="form-group">
        <label for="adults">Adults:</label>
        <select id="adults" name="adults" required>
            <option value="1">1</option>
            <option value="2">2</option>
            <option value="3">3</option>
            <option value="4">4</option>
            <option value="5">5</option>
        </select>
    </div>
    <div class="form-group">
        <label for="children">Children:</label>
        <select id="children" name="children">
            <option value="0">0</option>
            <option value="1">1</option>
            <option value="2">2</option>
            <option value="3">3</option>
            <option value="4">4</option>
        </select>
    </div>
    <div class="form-group">
        <label for="rooms">Rooms:</label>
        <select id="rooms" name="rooms" required>
            <option value="1">1</option>
            <option value="2">2</option>
            <option value="3">3</option>
        </select>
    </div>
    <div class="form-group">
        <label for="destination">Destination:</label>
        <input type="text" id="destination" name="destination" placeholder="Enter destination" required>
    </div>
    <button type="submit">Search Hotels</button>
</form>