<!DOCTYPE html>
<html lang="en">
<head>
   <title>Customers</title>
   <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}" crossorigin="anonymous">
   <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
</head>

<body>

<div class="container pt-5">
    <h4 class="mb-4">Customers</h4>

    <table class="table table-hover table-striped table-bordered" id="customers-table">
        <thead class="thead-dark">
            <tr>
                <th>Full Name</th>
                <th>Email</th>
                <th>Country</th>
            </tr>
        </thead>
        <tbody>
     
        </tbody>
    </table>
</div>


<div class="modal fade" id="customerDetailModal" tabindex="-1" aria-labelledby="customerDetailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="customerDetailModalLabel">Customer Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p><strong>Full Name:</strong> <span id="modal-full-name"></span></p>
        <p><strong>Email:</strong> <span id="modal-email"></span></p>
        <p><strong>Username:</strong> <span id="modal-username"></span></p>
        <p><strong>Gender:</strong> <span id="modal-gender"></span></p>
        <p><strong>Country:</strong> <span id="modal-country"></span></p>
        <p><strong>City:</strong> <span id="modal-city"></span></p>
        <p><strong>Phone:</strong> <span id="modal-phone"></span></p>
      </div>
    </div>
  </div>
</div>
</body>
</html>

<script src="{{ asset('js/jquery.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

<script>
    $(document).ready(function () {
        fetch("{{ url('customers') }}")
            .then(response => response.json())
            .then(data => {
                const tbody = $("#customers-table tbody");
                data.forEach(customer => {
                    const row = `
                        <tr data-id="${customer.id}" class="clickable-row">
                            <td>${customer.full_name}</td>
                            <td>${customer.email}</td>
                            <td>${customer.country}</td>
                        </tr>`;
                    tbody.append(row);
                });
              
                $('#customers-table').DataTable({
                    pageLength: 10,
                    language: {
                        emptyTable: "No data available yet. Please run <b>php artisan import:customers</b> command."
                    }
                });
                
                $(document).on('click', 'tr.clickable-row', function () {
                    const customerId = $(this).data('id');
                    
                    if (!customerId) return;

                    fetch(`{{ url('customers') }}/${customerId}`)
                        .then(response => response.json())
                        .then(customer => {
                            $('#modal-full-name').text(customer.full_name);
                            $('#modal-email').text(customer.email);
                            $('#modal-username').text(customer.username);
                            $('#modal-gender').text(customer.gender);
                            $('#modal-country').text(customer.country);
                            $('#modal-city').text(customer.city);
                            $('#modal-phone').text(customer.phone);

                            $('#customerDetailModal').modal('show');
                        })
                        .catch(err => {
                            alert('Failed to fetch customer details.');
                            console.error(err);
                        });
                });
            })
            .catch(error => console.error("Error loading customers:", error));
    });
</script>
