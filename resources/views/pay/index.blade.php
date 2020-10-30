<!DOCTYPE html>
<html>
<head>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
    <script src="https://hosted.paysafe.com/threedsecure/js/latest/paysafe.threedsecure.min.js"></script>

</head>
<body>
<form action="/Pay/store" method="post" class="form-controls">
    @csrf
    <fieldset>
        <legend>Card Details</legend>
        <div>
            <label>
                Holder Name:
                <input type="input" name="holder_Name" value="<?php

                ?>"/>
            </label>
        </div>
        <div>
            <label>
                Card Number:
                <input type="input" name="card_number" value="<?php

                    echo "4111111111111";
                ?>"/>
            </label>
        </div>
        <div>
            <label>
                card Expiry Month:
                <select name="card_exp_month">
                    <?php
                    $currentMonth = Date('n');
                    for ($i = 1; $i <= 12; $i++) {
                        echo '<option value="' . $i . '"' . (((isset($_POST['card_exp_month']) && $_POST['card_exp_month'] == $i) || (!isset($_POST['card_exp_month']) && $i == $currentMonth)) ? ' selected' : '') . '>' . DateTime::createFromFormat('!m', $i)->format('F') . '</option>';
                    }
                    ?>
                </select>
            </label>
        </div>
        <div>
            card Expiry Year:
            <select name="card_exp_year">
                <?php
                $currentYear = Date('Y');
                for ($i = $currentYear; $i < $currentYear + 5; $i++) {
                    echo '<option value="' . $i . '"' . (((isset($_POST['card_exp_year']) && $_POST['card_exp_year'] == $i) || (!isset($_POST['card_exp_year']) && $i == $currentYear)) ? ' selected' : '') . '>' . $i . '</option>';
                }
                ?>
            </select>
            </label>
        </div>
    </fieldset>
    <fieldset>

        <legend>Order Details</legend>
        <div>
            <label>
                Merchant Ref Num:
                <input type="input" name="merchant_ref_num" value="{{ uniqid(date('')) }}"/>
            </label>
        </div>
        <div>
            <label>
                Amount:
                <input type="input" name="amount" value="{{ 99999999 }}"/>
            </label>
        </div>
        <div>
            <label>
                Currency :
                <input type="input" autocomplete="off" name="currency" value="{{ "GBP" }}"/>
            </label>
        </div>
        <div>
            <label>
                deviceFingerprintingId :
                <input type="input" autocomplete="off" name="deviceFingerprinting_Id" value="" id="deviceFingerprinting_Id"/>
            </label>
        </div>
        <div>
            <label>
                Merchant URL  :
                <input type="input" autocomplete="off" name="merchant_Url" value="{{'https://mysite.com'}}"/>
            </label>
        </div>
        <div>
            <label>
                Authentication Purpose :
                <input type="input" autocomplete="off" name="authentication_Purpose" value="{{ 'PAYMENT_TRANSACTION' }}"/>
            </label>
        </div>
        <div>
            <label>
                Device Channel :
                <input type="input" autocomplete="off" name="device_Channel" value="{{ 'BROWSER' }}"/>
            </label>
        </div>
        <div>
            <label>
                Authentication Purpose :
                <input type="input" autocomplete="off" name="message_Category" value="{{ 'PAYMENT' }}"/>
            </label>
        </div>
    </fieldset>
    <br>
    <input type="submit" />
</form>
</body>
</html>


<script>
    $( document ).ready(function() {
        paysafe.threedsecure.start("dGVzdF9kZXNpcmVtZV90ZXN0OkItcWEyLTAtNWY5YWI2NTYtMC0zMDJjMDIxNDBiZGMxZTZlYmViZDYzYzk4NDg5YTM4NTYzNTE1MzMyOGRmZmVlNTEwMjE0NWVkNmZlODVmMTQ5M2NkYmE2MjBiMjY3NGNmNTY4MDYxM2Q2NWUzYQ==", {
            environment: "TEST",
            accountId: "1001849380",
            card: {
                cardBin: "41111111"
            }
        }, function (deviceFingerprintingId, error) {
            $('#deviceFingerprinting_Id').val(deviceFingerprintingId);
             console.log(deviceFingerprintingId);
        });
    });
</script>