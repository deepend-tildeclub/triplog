$(document).ready(function() {
    function loadTripList() {
        $.ajax({
            url: 'get_trips.php',
            type: 'GET',
            success: function(data) {
                $('#trip-list').html(data);
            },
            error: function(error) {
                console.error('Error fetching trip list:', error);
            }
        });
    }

    function printReport() {
        const printContents = $('#search-results').html();
        const originalContents = $('body').html();

        $('body').html(`
            <div class="print-header">
                <div class="print-logo">
                    <img src="https://bigfootbuilding.com/wp-content/uploads/2018/02/cropped-logo_black.png" alt="Logo" style="height: 70px; width: auto;">
                </div>
                <h1 class="print-title">Trip Log Report</h1>
            </div>
            ${printContents}`);
        
        $('.print-report').remove();

        window.print();
        $('body').html(originalContents);
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        const options = {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        };

        return new Intl.DateTimeFormat('en-US', options).format(date);
    }

    $('#search-form').on('submit', async function(event) {
        event.preventDefault();

        const search = $("#search").val();
        try {
            const response = await $.get(`search_trip_logs.php?search=${encodeURIComponent(search)}`);
            let output = "";

            if (response.length === 0) {
                output = "<p>No matching results found</p>";
            } else {
                output = `<ul class="search-results-list">`;
                response.forEach((result, index) => {
                    const formattedDate = formatDate(result.date_time);
                    output += `<li class="search-result" data-index="${index}">${result.driver_name} - Unit ${result.unit_number} - ${formattedDate}</li>`;
                });
                output += `</ul><div id="trip-details"></div>`;
            }

            $("#search-results").html(output);
            addClickEventToSearchResults(response);
        } catch (error) {
            console.error("Error searching trip logs:", error);
        }
    });

    function addClickEventToSearchResults(results) {
        $(".search-result").on("click", function() {
            const index = $(this).data("index");
            const result = results[index];
            const existingDetails = $(this).next(".trip-report");

            if (existingDetails.length) {
                existingDetails.slideToggle("fast", function() {
                    existingDetails.remove();
                });
                return;
            }

            let details = `
            <div class="trip-report">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Driver Name</th>
                            <th>Unit Number</th>
                            <th>Date/Time</th>
                            <th>Destination Address</th>
                            <th>Invoice Number</th>
                            <th>Comments</th>
                            <th>Image</th>
                            <th>Stages & Orders</th>
                        </tr>
                    </thead>
                    <tbody>`;
            
            result.destinations.forEach((destination, index) => {
                const stagesAndOrders = [
                    destination.stage1 ? 'Stage 1' : '',
                    destination.stage2 ? 'Stage 2' : '',
                    destination.sheets ? 'Sheets' : '',
                    destination.pickup ? 'Pick-Up' : '',
                    destination.backorder ? 'Back-Order' : ''
                ].filter(Boolean).join(', ');

                details += `
                <tr>
                    ${index === 0 ? `<td rowspan="${result.destinations.length}">${result.driver_name}</td>` : ''}
                    ${index === 0 ? `<td rowspan="${result.destinations.length}">${result.unit_number}</td>` : ''}
                    ${index === 0 ? `<td rowspan="${result.destinations.length}">${formatDate(result.date_time)}</td>` : ''}
                    <td>${destination.destination_address}</td>
                    <td>${destination.invoice_number}</td>
                    <td><pre style="white-space: pre-wrap; margin: 0;">${destination.comments}</pre></td>
                    <td>
                        ${destination.image_path ? `<a href="${destination.image_path}" target="_blank"><img src="images/pic.png" alt="View Image" width="20"></a>` : ''}
                    </td>
                    <td>${stagesAndOrders}</td>
                </tr>`;
            });

            details += `
                    </tbody>
                </table>
            </div>`;

            $(this).after(details);
            $(this).next(".trip-report").hide().slideToggle("fast");
        });
    }
});
