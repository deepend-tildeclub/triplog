<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trip Log</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="trip.js"></script>
</head>

<body>
    <div class="container">
        <h1>Bigfoot Trip Log</h1>
        <?php if (!empty($success_message)): ?>
        <div id="success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <form action="add_trip.php" method="post" enctype="multipart/form-data" id="tripForm">
            <label>
                Driver Name:
                <input type="text" name="driver_name" required>
            </label>
            <label>
                Unit Number:
                <input type="number" name="unit_number" required>
            </label>
            <label>
                Date/Time:
                <input type="datetime-local" name="date_time" required>
            </label>
            <div id="destination-container">
                <div class="destination">
                    <div class="destination-header">
                        <h4>Destination 1</h4>
                    </div>
                    <div class="destination-content">
                        <label>
                            Destination Address:
                            <input type="text" name="destination_address[]" required>
                        </label>
                        <label>
                            Invoice Number:
                            <input type="text" name="invoice_number[]" required>
                        </label>
                        <label>
                            Comments:
                            <textarea name="comments[]"></textarea>
                        </label>
                        <label>
                            <input type="checkbox" name="stage1_checked[]" value="1"> Stage 1
                        </label>
                        <label>
                            <input type="checkbox" name="stage2_checked[]" value="1"> Stage 2
                        </label>
                        <label>
                            <input type="checkbox" name="sheets_checked[]" value="1"> Sheets
                        </label>
                        <label>
                            <input type="checkbox" name="pickup_checked[]" value="1"> Pick-Up
                        </label>
                        <label>
                            <input type="checkbox" name="backorder_checked[]" value="1"> Back-Order
                        </label>
                        <label>
                            Photo of Delivery:
                            <input type="file" name="destination_image[]" accept="image/*">
                        </label>
                    </div>
                </div>
            </div>
            <div class="button-container">
                <button type="button" class="regular-button" id="add-destination">Add Another Destination</button>
                <input type="submit" class="submit-button" value="Add Trip Log">
            </div>
        </form>
        <h2>Search Trip Logs</h2>
        <form onsubmit="searchTripLogs(event);">
            <label>
                Search:
                <input type="text" id="search" required>
            </label>
            <input type="submit" value="Search">
        </form>
        <div id="search-results">
            <!-- Search results will be displayed here -->
        </div>
    </div>

<script>
$(document).ready(function() {
    let destinationCount = 1;

    function cloneDestination() {
        const destination = $(".destination").first().clone(true);
        destination.find("input, textarea").val("");
        destination.find("input[type=checkbox]").prop('checked', false);
        destination.find("input[type=file]").val("");
        return destination;
    }

    function toggleDestination(element) {
        const destination = $(element).closest(".destination");
        const content = destination.find(".destination-content");
        destination.toggleClass("collapsed");
        content.css("maxHeight", destination.hasClass("collapsed") ? content[0].scrollHeight + "px" : "0");
    }

    function reindexFormElements() {
        $(".destination").each(function(index) {
            $(this).find("input, textarea, select").each(function() {
                const regexPattern = /\[\d+\]/;
                if ($(this).attr('name').match(regexPattern)) {
                    $(this).attr('name', $(this).attr('name').replace(regexPattern, `[${index}]`));
                }
            });
        });
    }

    function debugFormData(formData) {
        for (const pair of formData.entries()) {
            console.log(`${pair[0]}: ${pair[1]}`);
        }
    }

    // Add destination listener
    $("#add-destination").click(function(event) {
        event.preventDefault();
        const destinationGroup = cloneDestination();
        destinationCount++;
        destinationGroup.find("h4").text(`Destination ${destinationCount}`);
        $("#destination-container").append(destinationGroup);

        reindexFormElements();
        destinationGroup.find(".destination-header").click(function() {
            toggleDestination(this);
        });

        toggleDestination(destinationGroup.find(".destination-header"));
    });

    // Toggle destination listeners
    $(".destination .destination-header").click(function() {
        toggleDestination(this);
    });

    // Expand the first destination by default
    toggleDestination($(".destination .destination-header").first());

    // Form submission listener for AJAX
    $('#tripForm').submit(function(event) {
        event.preventDefault();
        reindexFormElements();
        const formData = new FormData(this);
        debugFormData(formData);

        $(".destination").each(function(index) {
            const checkboxNames = ['stage1_checked', 'stage2_checked', 'sheets_checked', 'pickup_checked', 'backorder_checked'];
            checkboxNames.forEach(function(checkboxName) {
                const checkbox = $(this).find(`input[name="${checkboxName}[${index}]"]`);
                if (checkbox.length) {
                    console.log(`Processing checkbox ${checkboxName} for destination ${index + 1} (Checked: ${checkbox.prop('checked')})`);
                } else {
                    console.log(`Could not find checkbox ${checkboxName} for destination ${index + 1}`);
                }
            });
        });

        // Send the form data using jQuery AJAX
        $.ajax({
            url: 'add_trip.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if (response.success) {
                    alert("Trip log added successfully!");
                    location.reload(); // Reload the page
                } else {
                    alert("Error: " + response.message);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                alert("An error occurred: " + errorThrown);
            }
        });
    });

    // Checkbox change listener
    $("body").on("change", ".destination input[type='checkbox']", function() {
        const destinationIndex = $(".destination").index($(this).closest(".destination")) + 1;
        if ($(this).prop('checked')) {
            console.log('Checkbox in destination ' + destinationIndex + ' has been checked.');
        } else {
            console.log('Checkbox in destination ' + destinationIndex + ' has been unchecked.');
        }
    });
});
  </script>


</body>

</html>