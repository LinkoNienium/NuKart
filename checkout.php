
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();
session_start();
require_once 'config/connect.php';

// Redirect if not logged in
if (!isset($_SESSION['customer']) || empty($_SESSION['customer'])) {
    header('location: login.php');
    exit();
}

include 'inc/header.php';
include 'inc/nav.php';

$uid = $_SESSION['customerid'];
$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

// ✅ CALCULATE TOTAL
$total = 0;
foreach ($cart as $key => $value) {
    $ordsql = "SELECT * FROM products WHERE id=$key";
    $ordres = mysqli_query($connection, $ordsql);
    $ordr = mysqli_fetch_assoc($ordres);

    if ($ordr) {
        $total += $ordr['price'] * $value['quantity'];
    }
}

// ✅ FORM SUBMIT
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (isset($_POST['agree']) && $_POST['agree'] == 'true') {

        // ✅ Updated filters (no deprecated warning)
        $country = filter_var($_POST['country'], FILTER_SANITIZE_SPECIAL_CHARS);
        $fname   = filter_var($_POST['fname'], FILTER_SANITIZE_SPECIAL_CHARS);
        $lname   = filter_var($_POST['lname'], FILTER_SANITIZE_SPECIAL_CHARS);
        $company = filter_var($_POST['company'], FILTER_SANITIZE_SPECIAL_CHARS);
        $address1 = filter_var($_POST['address1'], FILTER_SANITIZE_SPECIAL_CHARS);
        $address2 = filter_var($_POST['address2'], FILTER_SANITIZE_SPECIAL_CHARS);
        $city    = filter_var($_POST['city'], FILTER_SANITIZE_SPECIAL_CHARS);
        $state   = filter_var($_POST['state'], FILTER_SANITIZE_SPECIAL_CHARS);
        $phone   = filter_var($_POST['phone'], FILTER_SANITIZE_NUMBER_INT);
        $payment = filter_var($_POST['payment'], FILTER_SANITIZE_SPECIAL_CHARS);
        $zip     = filter_var($_POST['zipcode'], FILTER_SANITIZE_NUMBER_INT);

        // Check existing usermeta
        $sql = "SELECT * FROM usersmeta WHERE uid=$uid";
        $res = mysqli_query($connection, $sql);
        $count = mysqli_num_rows($res);

        if ($count == 1) {
            // UPDATE
            $usql = "UPDATE usersmeta SET 
                country='$country',
                firstname='$fname',
                lastname='$lname',
                address1='$address1',
                address2='$address2',
                city='$city',
                state='$state',
                zip='$zip',
                company='$company',
                mobile='$phone'
                WHERE uid=$uid";

            mysqli_query($connection, $usql) or die(mysqli_error($connection));
        } else {
            // INSERT
            $isql = "INSERT INTO usersmeta 
                (country, firstname, lastname, address1, address2, city, state, zip, company, mobile, uid)
                VALUES 
                ('$country','$fname','$lname','$address1','$address2','$city','$state','$zip','$company','$phone','$uid')";

            mysqli_query($connection, $isql) or die(mysqli_error($connection));
        }

        // ✅ INSERT ORDER
        $iosql = "INSERT INTO orders (uid, totalprice, orderstatus, paymentmode) 
                  VALUES ('$uid', '$total', 'Order Placed', '$payment')";
        mysqli_query($connection, $iosql) or die(mysqli_error($connection));

        $orderid = mysqli_insert_id($connection);

        // ✅ INSERT ORDER ITEMS
        foreach ($cart as $key => $value) {
            $ordsql = "SELECT * FROM products WHERE id=$key";
            $ordres = mysqli_query($connection, $ordsql);
            $ordr = mysqli_fetch_assoc($ordres);

            if ($ordr) {
                $pid = $ordr['id'];
                $price = $ordr['price'];
                $qty = $value['quantity'];

                $orditmsql = "INSERT INTO orderitems 
                    (pid, orderid, productprice, pquantity)
                    VALUES ('$pid', '$orderid', '$price', '$qty')";

                mysqli_query($connection, $orditmsql) or die(mysqli_error($connection));
            }
        }

        unset($_SESSION['cart']);
        header("location: my-account.php");
        exit();
    }
}

// Fetch usermeta
$sql = "SELECT * FROM usersmeta WHERE uid=$uid";
$res = mysqli_query($connection, $sql);
$r = mysqli_fetch_assoc($res);
?>

<!-- SHOP CONTENT -->
<section id="content">
<div class="content-blog">
<div class="page_header text-center">
<h2>Checkout</h2>
</div>

<form method="post">
<div class="container">

<div class="row">
<div class="col-md-6 col-md-offset-3">

<div class="billing-details">
<h3>Billing Details</h3>

<label>Country</label>
<select name="country" class="form-control">
<option value="">Select</option>
<option value="India">India</option>
<option value="Other">Other</option>
</select>

<label>First Name</label>
<input name="fname" class="form-control" value="<?= $r['firstname'] ?? '' ?>">

<label>Last Name</label>
<input name="lname" class="form-control" value="<?= $r['lastname'] ?? '' ?>">

<label>Department</label>
<input name="company" class="form-control" value="<?= $r['company'] ?? '' ?>">

<label>Address</label>
<input name="address1" class="form-control" value="<?= $r['address1'] ?? '' ?>">

<label>City</label>
<input name="city" class="form-control" value="<?= $r['city'] ?? '' ?>">

<label>State</label>
<input name="state" class="form-control" value="<?= $r['state'] ?? '' ?>">

<label>Zip</label>
<input name="zipcode" class="form-control" value="<?= $r['zip'] ?? '' ?>">

<label>Phone</label>
<input name="phone" class="form-control" value="<?= $r['mobile'] ?? '' ?>">

</div>
</div>
</div>

<h4>Your Order</h4>

<table class="table table-bordered">
<tr>
<th>Subtotal</th>
<td>₹<?= number_format($total, 2) ?></td>
</tr>

<tr>
<th>Shipping</th>
<td>Free</td>
</tr>

<tr>
<th>Total</th>
<td><strong>₹<?= number_format($total, 2) ?></strong></td>
</tr>
</table>

<h4>Payment</h4>

<input type="radio" name="payment" value="cod"> Cash on Delivery<br>
<input type="radio" name="payment" value="cheque"> Cheque<br>
<input type="radio" name="payment" value="paypal"> Paypal<br><br>

<input type="checkbox" name="agree" value="true"> Accept Terms<br><br>

<input type="submit" class="btn btn-success" value="Place Order">

</div>
</form>

</div>
</section>

<?php include 'inc/footer.php'; ?>