<?php
wp_nonce_field( 'svc_visit_form', 'svc_nonce' );
?>
<form id="svc-visit-form" method="post" enctype="multipart/form-data">
    <p>
        <label for="svc_store">Store Name:</label><br />
        <input type="text" name="svc_store" id="svc_store" />
    </p>
    <p>
        <label for="svc_contact">Contact Person:</label><br />
        <input type="text" name="svc_contact" id="svc_contact" />
    </p>
    <p>
        <label for="svc_tasks">Tasks (one per line):</label><br />
        <textarea name="svc_tasks" id="svc_tasks" rows="5" cols="30"></textarea>
    </p>
    <p>
        <label for="svc_photo">Photo:</label><br />
        <input type="file" name="svc_photo" id="svc_photo" accept="image/*" capture="environment" />
    </p>
    <p>
        <label for="svc_rating">Rating:</label>
        <select name="svc_rating" id="svc_rating">
            <option value="5">5</option>
            <option value="4">4</option>
            <option value="3">3</option>
            <option value="2">2</option>
            <option value="1">1</option>
        </select>
    </p>
    <p>
        <label for="svc_next_visit">Next Visit Date:</label><br />
        <input type="date" name="svc_next_visit" id="svc_next_visit" />
    </p>
    <input type="hidden" name="svc_lat" id="svc_lat" />
    <input type="hidden" name="svc_lng" id="svc_lng" />
    <p><input type="submit" value="Submit Visit" /></p>
</form>
