<fieldset>
    <div class="card card-primary card-outline card-outline-tabs">
            <div class="card-header p-0 border-bottom-0">
                <ul class="nav nav-tabs flex-wrap" role="tablist">
                  <li class="nav-item">
                    <a class="nav-link active" id="tab-text" data-toggle="pill" href="#text" role="tab" aria-controls="text" aria-selected="true">Text <i class="fas fa-align-left"></i></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="tab-email" data-toggle="pill" href="#email" role="tab" aria-controls="email" aria-selected="false">Email <i class="far fa-envelope"></i></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="tab-phone" data-toggle="pill" href="#phone" role="tab" aria-controls="phone" aria-selected="false">Phone <i class="fas fa-phone-volume"></i></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="tab-sms" data-toggle="pill" href="#sms" role="tab" aria-controls="sms" aria-selected="false">Sms <i class="far fa-comments"></i></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="tab-whatsapp" data-toggle="pill" href="#whatsapp" role="tab" aria-controls="whatsapp" aria-selected="false">Whatsapp <i class="fab fa-whatsapp"></i></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="tab-skype" data-toggle="pill" href="#skype" role="tab" aria-controls="skype" aria-selected="false">Skype <i class="fab fa-skype"></i></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="tab-location" data-toggle="pill" href="#location" role="tab" aria-controls="location" aria-selected="false">Location <i class="fas fa-map-marker-alt"></i></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="tab-vcard" data-toggle="pill" href="#vcard" role="tab" aria-controls="vcard" aria-selected="false">Vcard 4.0 <i class="far fa-address-card"></i></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="tab-mecard" data-toggle="pill" href="#mecard" role="tab" aria-controls="mecard" aria-selected="false">MeCard <i class="fas fa-id-badge"></i></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="tab-event" data-toggle="pill" href="#event" role="tab" aria-controls="event" aria-selected="false">Event <i class="fas fa-calendar-alt"></i></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="tab-bookmark" data-toggle="pill" href="#bookmark" role="tab" aria-controls="bookmark" aria-selected="false">Bookmark <i class="far fa-bookmark"></i></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="tab-wifi" data-toggle="pill" href="#wifi" role="tab" aria-controls="wifi" aria-selected="false">Wifi <i class="fas fa-wifi"></i></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="tab-paypal" data-toggle="pill" href="#paypal" role="tab" aria-controls="paypal" aria-selected="false">Paypal <i class="fab fa-paypal"></i></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="tab-bitcoin" data-toggle="pill" href="#bitcoin" role="tab" aria-controls="bitcoin" aria-selected="false">Bitcoin <i class="fab fa-bitcoin"></i></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="tab-crypto" data-toggle="pill" href="#crypto" role="tab" aria-controls="crypto" aria-selected="false">Crypto <i class="fab fa-ethereum"></i></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="tab-sepa" data-toggle="pill" href="#sepa" role="tab" aria-controls="sepa" aria-selected="false">SEPA <i class="fas fa-university"></i></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="tab-pix" data-toggle="pill" href="#pix" role="tab" aria-controls="pix" aria-selected="false">Pix <i class="fas fa-money-bill-wave"></i></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="tab-telegram" data-toggle="pill" href="#telegram" role="tab" aria-controls="telegram" aria-selected="false">Telegram <i class="fab fa-telegram"></i></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="tab-social" data-toggle="pill" href="#social" role="tab" aria-controls="social" aria-selected="false">Social <i class="fas fa-share-alt"></i></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="tab-meeting" data-toggle="pill" href="#meeting" role="tab" aria-controls="meeting" aria-selected="false">Meeting <i class="fas fa-video"></i></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="tab-medical" data-toggle="pill" href="#medical" role="tab" aria-controls="medical" aria-selected="false">Medical ID <i class="fas fa-first-aid"></i></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="tab-twofa" data-toggle="pill" href="#twofa" role="tab" aria-controls="twofa" aria-selected="false">2FA <i class="fa fa-key"></i></a>
                  </li>
                </ul>
            </div>
              <div class="card-body">
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="text" role="tabpanel" aria-labelledby="tab-text">
                        <?php include BASE_PATH . '/forms/static/text.php'; ?>      
                    </div>
                    <div class="tab-pane fade" id="email" role="tabpanel" aria-labelledby="tab-email">
                        <?php include BASE_PATH . '/forms/static/email.php'; ?>  
                    </div>
                    <div class="tab-pane fade" id="phone" role="tabpanel" aria-labelledby="tab-phone">
                        <?php include BASE_PATH . '/forms/static/phone.php'; ?>  
                    </div>
                    <div class="tab-pane fade" id="sms" role="tabpanel" aria-labelledby="tab-sms">
                        <?php include BASE_PATH . '/forms/static/sms.php'; ?>  
                    </div>
                    <div class="tab-pane fade" id="whatsapp" role="tabpanel" aria-labelledby="tab-whatsapp">
                        <?php include BASE_PATH . '/forms/static/whatsapp.php'; ?>  
                    </div>
                    <div class="tab-pane fade" id="skype" role="tabpanel" aria-labelledby="tab-skype">
                        <?php include BASE_PATH . '/forms/static/skype.php'; ?>  
                    </div>
                    <div class="tab-pane fade" id="location" role="tabpanel" aria-labelledby="tab-location">
                        <?php include BASE_PATH . '/forms/static/location.php'; ?>  
                    </div>
                    <div class="tab-pane fade" id="vcard" role="tabpanel" aria-labelledby="tab-vcard">
                        <?php include BASE_PATH . '/forms/static/vcard.php'; ?>  
                    </div>
                    <div class="tab-pane fade" id="mecard" role="tabpanel" aria-labelledby="tab-mecard">
                        <?php include BASE_PATH . '/forms/static/mecard.php'; ?>  
                    </div>
                    <div class="tab-pane fade" id="event" role="tabpanel" aria-labelledby="tab-event">
                        <?php include BASE_PATH . '/forms/static/event.php'; ?>  
                    </div>
                    <div class="tab-pane fade" id="bookmark" role="tabpanel" aria-labelledby="tab-bookmark">
                        <?php include BASE_PATH . '/forms/static/bookmark.php'; ?>  
                    </div>
                    <div class="tab-pane fade" id="wifi" role="tabpanel" aria-labelledby="tab-wifi">
                        <?php include BASE_PATH . '/forms/static/wifi.php'; ?>  
                    </div>
                    <div class="tab-pane fade" id="paypal" role="tabpanel" aria-labelledby="tab-paypal">
                        <?php include BASE_PATH . '/forms/static/paypal.php'; ?>  
                    </div>
                    <div class="tab-pane fade" id="bitcoin" role="tabpanel" aria-labelledby="tab-bitcoin">
                        <?php include BASE_PATH . '/forms/static/bitcoin.php'; ?>  
                    </div>
                    <div class="tab-pane fade" id="crypto" role="tabpanel" aria-labelledby="tab-crypto">
                        <?php include BASE_PATH . '/forms/static/crypto.php'; ?>  
                    </div>
                    <div class="tab-pane fade" id="sepa" role="tabpanel" aria-labelledby="tab-sepa">
                        <?php include BASE_PATH . '/forms/static/sepa.php'; ?>  
                    </div>
                    <div class="tab-pane fade" id="pix" role="tabpanel" aria-labelledby="tab-pix">
                        <?php include BASE_PATH . '/forms/static/pix.php'; ?>  
                    </div>
                    <div class="tab-pane fade" id="telegram" role="tabpanel" aria-labelledby="tab-telegram">
                        <?php include BASE_PATH . '/forms/static/telegram.php'; ?>  
                    </div>
                    <div class="tab-pane fade" id="social" role="tabpanel" aria-labelledby="tab-social">
                        <?php include BASE_PATH . '/forms/static/social.php'; ?>  
                    </div>
                    <div class="tab-pane fade" id="meeting" role="tabpanel" aria-labelledby="tab-meeting">
                        <?php include BASE_PATH . '/forms/static/meeting.php'; ?>  
                    </div>
                    <div class="tab-pane fade" id="medical" role="tabpanel" aria-labelledby="tab-medical">
                        <?php include BASE_PATH . '/forms/static/medical.php'; ?>  
                    </div>
                    <div class="tab-pane fade" id="twofa" role="tabpanel" aria-labelledby="tab-twofa">
                        <?php include BASE_PATH . '/forms/static/2fa.php'; ?>  
                    </div>
                </div>
              </div>
    </div><!-- /.card -->
</fieldset>
