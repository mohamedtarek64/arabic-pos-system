// Function to display messages
function showMessage(type, message) {
    const messageContainer = document.getElementById('message-container');
    messageContainer.classList.remove('error', 'success');
    messageContainer.classList.add(type);
    messageContainer.innerText = message;
    messageContainer.style.display = 'block';  // Show message container
}

// Handling form submission for adding customer
document.getElementById("add_customer_form").addEventListener("submit", async function(event) {
    event.preventDefault();

    const name = document.getElementById("name").value;
    const phone = document.getElementById("phone").value;
    const address = document.getElementById("address").value;
    const email = document.getElementById("email").value;

    // Validate that all fields are filled out
    if (!name || !phone || !address) {
        showMessage("error", "Please fill in all fields.");
        return;
    }

    // Validate phone number (must be 11 digits)
    const phoneRegex = /^[0-9]{11}$/;
    if (!phoneRegex.test(phone)) {
        showMessage("error", "Phone number must be 11 digits.");
        return;
    }

    // Validate email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (email && !emailRegex.test(email)) {
        showMessage("error", "Invalid email address.");
        return;
    }

    // Sending data to the server using AJAX (fetch)
    const data = new URLSearchParams();
    data.append('name', name);
    data.append('phone', phone);
    data.append('address', address);
    data.append('email', email);

    try {
        const response = await fetch('customers.php', {
            method: 'POST',
            body: data,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        });
        const responseData = await response.json();
        if (responseData.success) {
            showMessage("success", "Customer added successfully.");
            document.getElementById("add_customer_form").reset();  // Reset form after successful addition
        } else {
            showMessage("error", responseData.message);
        }
    } catch (error) {
        showMessage("error", "There was an error with the request. Please try again.");
        console.error(error);
    }
});

// Handling search customer form submission
document.getElementById("search_customer_form").addEventListener("submit", async function(event) {
    event.preventDefault();

    const searchQuery = document.getElementById("search_query").value;

    if (!searchQuery) {
        showMessage("error", "Please enter a customer name to search.");
        return;
    }

    // Sending search query using AJAX (fetch)
    const data = new URLSearchParams();
    data.append('searchQuery', searchQuery);

    try {
        const response = await fetch('search_customer.php', {
            method: 'POST',
            body: data,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        });
        const responseData = await response.json();
        if (responseData.success) {
            document.getElementById("customer_info").innerHTML = responseData.customer;
        } else {
            showMessage("error", responseData.message);
        }
    } catch (error) {
        showMessage("error", "There was an error while searching. Please try again.");
        console.error(error);
    }
});

// Handling the addition of a product
document.getElementById('add_product').addEventListener('click', function(event) {
    event.preventDefault();

    // Collecting entered data
    const productName = document.getElementById('product_name').value;
    const quantity = document.getElementById('quantity').value;
    const price = 100; // Default price for the product
    const total = price * quantity;

    // Adding product to the table
    const table = document.querySelector('#added_products table tbody');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td>${productName}</td>
        <td>${quantity}</td>
        <td>${price}</td>
        <td>${total}</td>
    `;
    table.appendChild(row);

    // Updating the calculation
    updateCalculation();
});

// Function to update calculation (subtotal, total)
function updateCalculation() {
    let subtotal = 0;
    const rows = document.querySelectorAll('#added_products table tbody tr');
    rows.forEach(row => {
        const total = row.cells[3].innerText;
        subtotal += parseFloat(total);
    });

    const discount = document.getElementById('discount_percentage').value;
    const gst = document.getElementById('gst_percentage').value;

    const discountAmount = (subtotal * discount) / 100;
    const gstAmount = (subtotal * gst) / 100;

    const total = subtotal - discountAmount + gstAmount;

    document.getElementById('subtotal').innerText = subtotal;
    document.getElementById('total').innerText = total;
}

// Handling the save order button click
document.getElementById('save_order').addEventListener('click', function() {
    alert('Order saved successfully');
});

// Function to sort table by column
function sortTable(n) {
    var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
    table = document.getElementById("dataTable");
    switching = true;
    dir = "asc"; // Default direction is ascending
    while (switching) {
        switching = false;
        rows = table.rows;
        for (i = 1; i < (rows.length - 1); i++) {
            shouldSwitch = false;
            x = rows[i].getElementsByTagName("TD")[n];
            y = rows[i + 1].getElementsByTagName("TD")[n];
            if (dir == "asc") {
                if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
                    shouldSwitch = true;
                    break;
                }
            } else if (dir == "desc") {
                if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
                    shouldSwitch = true;
                    break;
                }
            }
        }
        if (shouldSwitch) {
            rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
            switching = true;
            switchcount++;
        } else {
            if (switchcount == 0 && dir == "asc") {
                dir = "desc";
                switching = true;
            }
        }
    }
}
document.querySelectorAll('.dropdown > a').forEach(function (dropdown) {
    dropdown.addEventListener('click', function () {
        this.parentElement.classList.toggle('open');
    });
});
