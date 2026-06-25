<?php include('header.php') ?>

<script>
    
    var btn = document.querySelectorAll("form .btn")[0];

var checkBox = document.getElementById("exampleCheck1");
btn.disabled = !checkBox.checked;

checkBox.addEventListener("change",checkTerms);

function checkTerms(){
	if(this.checked){
		btn.disabled = false;
	}else{
		btn.disabled = true;
	}
}




</script>
<div class="container">
	<h1>Legal Terms</h1>
	<form>
		<div class="form-group">
		<p>
Sunrise Academy ensures a secure and transparent online payment process for all its educational and associated services. By making a payment through our online platform, users agree to comply with the following policy.

All payments made via our website, UPI, bank transfer, or any digital gateway are final and must be completed using authorized methods. Users are responsible for providing accurate payment details. Sunrise Academy does not store any sensitive payment information like card or bank credentials. We use third-party payment gateways that comply with RBI guidelines and data protection norms.

No refund or cancellation is allowed once payment is successfully processed, except in cases of genuine technical error or duplicate transaction, subject to verification and management approval. In such cases, a written request must be submitted within 3 working days.

Sunrise Academy will not be liable for any unauthorized transaction conducted outside our official payment portals. Legal action may be taken in case of fraudulent activity or misuse of payment channels.
		</p>
		</div>
		<!--<div class="form-check">-->
		<!--	<input type="checkbox" class="form-check-input" id="exampleCheck1" checked>-->
		<!--	<label class="form-check-label" for="exampleCheck1">I have read and accept the terms of contract</label>-->
		<!--</div>-->
		<!--<button type="submit" class="my-3 btn btn-danger">Register</button>-->
	</form>
</div>
<?php include('footer.php') ?>