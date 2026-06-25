<?php include('header1.php'); ?>
<?php
include('config.php');
session_start();
	$use=$_SESSION['username'] ;
	
	
	

				$sql="select * from  students where mobile_number='$use'";
				$res=$con->query($sql);
			$row2=$res->fetch_array();
				
			  $name= $row2['name'];
			   $sid= $row2['registration_code'];
			   $img= $row2['image'];
			   $id= $row2['id'];

?>

<style>
table {
  border-collapse: collapse;
  width: 100%;
}
th, td {
  text-align: left;
  padding: 8px;
}
tr:nth-child(even) {background-color: #f2f2f2;}
</style>

<div class="page-content-wrapper py-3">
  <div class="container">
    <div class="card user-data-card">
      <div class="card-body">
        <h5 class="mb-3">My Notices</h5>
        <div style="overflow-x: auto;">
          <table>
            <tr>
              <th>Type</th>
              <th>Content</th>
              <th>Date</th>
            </tr>
            <?php 
            $sql = "SELECT * FROM notices WHERE student_id = '$id' ORDER BY id DESC";
            $res = $con->query($sql);
            while ($row = $res->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . ucfirst($row['notice_type']) . "</td>";

                if ($row['notice_type'] === 'text') {
                    echo "<td>" . nl2br(htmlspecialchars($row['notice_content'])) . "</td>";
                } elseif ($row['notice_type'] === 'image') {
                    echo "<td><img src='../yuva/template/" . $row['notice_content'] . "' alt='Notice Image' style='max-width:150px;'></td>";
                } elseif ($row['notice_type'] === 'video') {
                    echo "<td><video controls style='max-width:200px;'><source src='../yuva/template/" . $row['notice_content'] . "' type='video/mp4'>Your browser does not support the video tag.</video></td>";
                } else {
                    echo "<td>Unknown type</td>";
                }

                $date = isset($row['created_at']) ? $row['created_at'] : 'N/A';
                echo "<td>$date</td>";
                echo "</tr>";
            }
            ?>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include('footer.php'); ?>
