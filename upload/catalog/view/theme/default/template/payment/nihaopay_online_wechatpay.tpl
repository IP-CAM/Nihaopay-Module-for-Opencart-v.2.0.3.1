<div class="buttons">
  <div class="pull-right">
    <input type="button" value="<?php echo $button_confirm; ?>" id="button-confirm" class="btn btn-primary" />
  </div>
</div>

<script type="text/javascript"><!--
$('#button-confirm').on('click', function() {
	$.ajax({
		url: 'index.php?route=payment/nihaopay_online_wechatpay/send',
		type: 'post',
		dataType: 'json',
		cache: false,
		beforeSend: function() {
			$('#button-confirm').button('loading');
		},
		complete: function() {
			$('#button-confirm').button('reset');
		},
		success: function(json) {
			if (json['error']) {
				alert(json['error']);
			}

			if (json['html']) {
				document.write(json['html']);
			}
		}
	});
});
//--></script>