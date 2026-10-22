<fieldset>
    <div class="card card-primary card-outline card-outline-tabs">
            <div class="card-header p-0 border-bottom-0">
                <ul class="nav nav-tabs flex-wrap" role="tablist">
                  <li class="nav-item">
                    <a class="nav-link active" data-toggle="pill" href="#text" role="tab" aria-selected="true">Text <i class="fas fa-align-left"></i></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#email" role="tab" aria-selected="false">Email <i class="far fa-envelope"></i></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#phone" role="tab" aria-selected="false">Phone <i class="fas fa-phone-volume"></i></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#sms" role="tab" aria-selected="false">Sms <i class="far fa-comments"></i></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#whatsapp" role="tab" aria-selected="false">Whatsapp <i class="fab fa-whatsapp"></i></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#skype" role="tab" aria-selected="false">Skype <i class="fab fa-skype"></i></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#location" role="tab" aria-selected="false">Location <i class="fas fa-map-marker-alt"></i></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#vcard" role="tab" aria-selected="false">Vcard 4.0 <i class="far fa-address-card"></i></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#mecard" role="tab" aria-selected="false">MeCard <i class="fas fa-id-badge"></i></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#event" role="tab" aria-selected="false">Event <i class="fas fa-calendar-alt"></i></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#bookmark" role="tab" aria-selected="false">Bookmark <i class="far fa-bookmark"></i></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#wifi" role="tab" aria-selected="false">Wifi <i class="fas fa-wifi"></i></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#paypal" role="tab" aria-selected="false">Paypal <i class="fab fa-paypal"></i></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#bitcoin" role="tab" aria-selected="false">Bitcoin <i class="fab fa-bitcoin"></i></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#crypto" role="tab" aria-selected="false">Crypto <i class="fab fa-ethereum"></i></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#sepa" role="tab" aria-selected="false">SEPA <i class="fas fa-university"></i></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#pix" role="tab" aria-selected="false">Pix <i class="fas fa-money-bill-wave"></i></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#telegram" role="tab" aria-selected="false">Telegram <i class="fab fa-telegram"></i></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#social" role="tab" aria-selected="false">Social <i class="fas fa-share-alt"></i></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#meeting" role="tab" aria-selected="false">Meeting <i class="fas fa-video"></i></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#medical" role="tab" aria-selected="false">Medical ID <i class="fas fa-first-aid"></i></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#twofa" role="tab" aria-selected="false">2FA <i class="fa fa-key"></i></a>
                  </li>
                </ul>
            </div>
              <div class="card-body">
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="text" role="tabpanel">
                        <?php include BASE_PATH . '/forms/static/text.php'; ?>      
                    </div>
                    <div class="tab-pane fade" id="email" role="tabpanel">
                        <?php include BASE_PATH . '/forms/static/email.php'; ?>  
                    </div>
                    <div class="tab-pane fade" id="phone" role="tabpanel">
                        <?php include BASE_PATH . '/forms/static/phone.php'; ?>  
                    </div>
                    <div class="tab-pane fade" id="sms" role="tabpanel">
                        <?php include BASE_PATH . '/forms/static/sms.php'; ?>  
                    </div>
                    <div class="tab-pane fade" id="whatsapp" role="tabpanel">
                        <?php include BASE_PATH . '/forms/static/whatsapp.php'; ?>  
                    </div>
                    <div class="tab-pane fade" id="skype" role="tabpanel">
                        <?php include BASE_PATH . '/forms/static/skype.php'; ?>  
                    </div>
                    <div class="tab-pane fade" id="location" role="tabpanel">
                        <?php include BASE_PATH . '/forms/static/location.php'; ?>  
                    </div>
                    <div class="tab-pane fade" id="vcard" role="tabpanel">
                        <?php include BASE_PATH . '/forms/static/vcard.php'; ?>  
                    </div>
                    <div class="tab-pane fade" id="mecard" role="tabpanel">
                        <?php include BASE_PATH . '/forms/static/mecard.php'; ?>  
                    </div>
                    <div class="tab-pane fade" id="event" role="tabpanel">
                        <?php include BASE_PATH . '/forms/static/event.php'; ?>  
                    </div>
                    <div class="tab-pane fade" id="bookmark" role="tabpanel">
                        <?php include BASE_PATH . '/forms/static/bookmark.php'; ?>  
                    </div>
                    <div class="tab-pane fade" id="wifi" role="tabpanel">
                        <?php include BASE_PATH . '/forms/static/wifi.php'; ?>  
                    </div>
                    <div class="tab-pane fade" id="paypal" role="tabpanel">
                        <?php include BASE_PATH . '/forms/static/paypal.php'; ?>  
                    </div>
                    <div class="tab-pane fade" id="bitcoin" role="tabpanel">
                        <?php include BASE_PATH . '/forms/static/bitcoin.php'; ?>  
                    </div>
                    <div class="tab-pane fade" id="crypto" role="tabpanel">
                        <?php include BASE_PATH . '/forms/static/crypto.php'; ?>  
                    </div>
                    <div class="tab-pane fade" id="sepa" role="tabpanel">
                        <?php include BASE_PATH . '/forms/static/sepa.php'; ?>  
                    </div>
                    <div class="tab-pane fade" id="pix" role="tabpanel">
                        <?php include BASE_PATH . '/forms/static/pix.php'; ?>  
                    </div>
                    <div class="tab-pane fade" id="telegram" role="tabpanel">
                        <?php include BASE_PATH . '/forms/static/telegram.php'; ?>  
                    </div>
                    <div class="tab-pane fade" id="social" role="tabpanel">
                        <?php include BASE_PATH . '/forms/static/social.php'; ?>  
                    </div>
                    <div class="tab-pane fade" id="meeting" role="tabpanel">
                        <?php include BASE_PATH . '/forms/static/meeting.php'; ?>  
                    </div>
                    <div class="tab-pane fade" id="medical" role="tabpanel">
                        <?php include BASE_PATH . '/forms/static/medical.php'; ?>  
                    </div>
                    <div class="tab-pane fade" id="twofa" role="tabpanel">
                        <?php include BASE_PATH . '/forms/static/2fa.php'; ?>  
                    </div>
                </div>
              </div>
    </div><!-- /.card -->
</fieldset>
