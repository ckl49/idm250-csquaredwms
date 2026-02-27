<?php 
    function fetch_mpl($conn) {
        $sql = "SELECT id AS mpl_id, order_number, truck_number, expected_delivery FROM mpl";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $mpl_array = [];
            while($row = $result->fetch_assoc()) {
                $mpl_array[] = $row;
            }
            return ['success' => true, 'data' => $mpl_array];
        } else {
            return ['success' => false, 'error' => 'No orders found'];
        }
    }


    function edit_mpl() {

    }

?>