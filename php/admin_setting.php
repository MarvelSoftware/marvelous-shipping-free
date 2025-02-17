<?php

// =======================================================================================
// =======================================================================================

function admin_settings_ui(){
    try {

        define('MARVELOUS_SHIPPING_URL', plugin_dir_url(__DIR__));

        global $israel_heb2en;
        global $israel_districts_heb2en;
        global $info_svg_icon;
        global $spinner_svg;
        global $product_version;
        include_once(plugin_dir_path(__FILE__) . "kernel.php");
        include_once(plugin_dir_path(__FILE__) . "../cities/IL_cities.php");
        include_once(plugin_dir_path(__FILE__) . "constants.php");

        $user_options = get_user_options();
        $cities_data = get_all_cities_data();
        $messages = get_all_messages_data();

        // Output HTML for your settings fields here
        echo '<div class="wrap">';
        // spinner
        echo '<div id="spinner-overlay-id" class="spinner-overlay active">';
        echo '<div id="loader-container-id" class="loader-container active">';
        echo $spinner_svg;
        echo '<h2 class="h2-style" style="margin:10px; margin-top:20px; font-size: 26px;" >טוען תוסף נא להמתין</h2>';
        echo '</div>';
        echo '</div>';


        echo '<div class="msg-overlay" id="spinner-msg-overlay" style="display: none;">
                <div  id="spinner-msg-window" class="msg-window">
                    <img class="mrvl-bg" src="' . MARVELOUS_SHIPPING_URL . 'images/logo.svg" alt="marvel logo">

                    <div class="top-bar">
                        <p class="top-bar-title">הודעת מערכת</p>
                        <div class="close-button">X</div>
                    </div>
                    <div class="msg-area">
                        <div id="msg-icon"></div>
                        <p class="msg-content">Placeholder Text</p>
                    </div>
                    <div class="buttons-area">
                        <button class="cancel-button">ביטול</button>
                        <button id="msg-confirm-button" class="cool-button">אישור</button>
                    </div>
                </div>
            </div>';


        // settings body
        echo '<div class="marvel-news-area">
                <div class="marvel-news-content-div" style="animation: slide-left 45s linear infinite alternate;">';

        echo '
            <p>🔥 <span class="marvel-news-title" style="color:#ff8c00 !important; font-weight: 600;">פרימיום:</span> שדרגו ל-<span style="color:green !important; font-weight: 600;">Pro</span> או ל-<span style="color:goldenrod !important; font-weight: 600;">Ultimate</span> וקבלו פיצ\'רים מתקדמים, מיקוד אוטומטי, משלוחים חכמים, התאמות לפי משקל, קומות, ועוד!</p>
            <p>⚡ <span class="marvel-news-title" style="color:#007bff !important; font-weight: 600;">עדכונים:</span> רק משתמשי <span style="color:green !important; font-weight: 600;">Pro</span> / <span style="color:goldenrod !important; font-weight: 600;">Ultimate</span> מקבלים עדכונים שוטפים לקובץ הערים!</p>
            <p>🚨 <span class="marvel-news-title" style="color:#dc143c !important; font-weight: 600;">שידרוג מהיר:</span> <strong>תהליך שידרוג מהיר וקל תוך פחות מ-2 דקות דרך</strong> <a href="https://mrvlsol.co.il"  target="_blank" style="color:#007bff; text-decoration: underline;">mrvlsol.co.il</a> 🚀</p>
            <p>📦 <span class="marvel-news-title" style="color:green !important; font-weight: 600;">משלוח חינם:</span> בגרסאות <span style="color:green !important; font-weight: 600;">Premium</span> ניתן להגדיר <strong>משלוח חינם מעל X ש"ח!</strong></p>
            <p>💡 <span class="marvel-news-title" style="color:#ff00ff !important; font-weight: 600;">שליטה מתקדמת:</span> ב-<span style="color:goldenrod !important; font-weight: 600;">Ultimate</span> תוכלו לנהל משלוחים לפי <strong>מרחק</strong> או לפי <strong>אזורים</strong>, למצוא <strong>מיקוד אוטומטי</strong>, וליצור <strong>גיבויים חכמים!</strong></p>
        ';

        echo    '</div>
            </div>
        ';
        
        if(isMobile()){
            echo '<div class="license-box" style="margin: 20% 0">
                    <div class="contact-icons-row">
                        <img id="main-mrvl-image" class="main-mrvl-image" src="' . MARVELOUS_SHIPPING_URL . 'images/logo.svg" alt="marvel logo" onclick="openMarvelWebsite()">
                    </div>
                    <p class="license-message">אין כרגע תמיכה במובייל!<br>יש להתחבר מהמחשב.</p>                    
                </div>';
                return;
        }
        
        echo '<div class="main-mrvl-flex">';

        echo '<div class="right-settings-flex">';




        echo '<h1 style="position:relative; font-family: Fredoka, sans-serif;font-weight: 500;font-size: 35px;margin-top:20px">' .
        'MarvelousShipping <span style="font-family: Fredoka, sans-serif;font-weight: 500;font-size: 35px;margin-top:20px; color:gray !important">Free </span>' . 
        '<span style="font-size:17px"> v' .   $product_version . '</span>';
        echo '<button id="upgrade-to-premium-btn"  class="export-button upgrade-button">שדרגו לפרימיום</button>';

        echo '</h1>';
        echo '<h4 class="h4-style" >' . 'אהבתם את התוסף? רוצים פונקציונליות מיוחדת? מצאתם בעיה?<br>צרו קשר ב-<a href="https://mrvlsol.co.il/?utm_source=marvelous_shipping_plugin_admin_ui" target="_blank" >Marvel Software Solutions</a>.' . '</h4>';
        echo '<img id="main-mrvl-image" class="main-mrvl-image" src="' . MARVELOUS_SHIPPING_URL . 'images/logo.svg" alt="marvel logo" onclick="openMarvelWebsite()">';

        // ================================================================================
        // ================================================================================
        
        echo '<h2 class="h2-style" >נתוני תוסף</h2>';


        echo '
        <div class="info-row-lable">
            <div style="font-family: Fredoka, sans-serif;min-width: 150px">
                <p class="lable-style">רישיון:</p>
            </div>
            <p class="lable-style" style="color: gray; font-weight:550 !important">חוקי, חינמי</p>
        </div>';

        echo '<!-- Field -->
        <div class="info-row-lable">
            <div style="font-family: Fredoka, sans-serif;min-width: 150px">
                <p class="lable-style"">צריכים עזרה?</p>
            </div>
            <a href="https://mrvlsol.co.il/?marvelous_shipping_support&utm_source=wp_admin_free_ver" target="_blank" style="font-family: Fredoka, sans-serif;color: lightred; font-weight: 500; text-decoration: none; font-size: 18px; visited { color: blue !important; }">בקרו בפורום התמיכה</a>
        </div>';


        //<h4 class="feature-version-free">FREE</h4>

        echo '
        <div class="header-with-info">
            <h2 class="h2-style">הגדרות שדות</h2>
            <div class="info-icon-container">
                '.$info_svg_icon.'
                <div class="info-window" style="padding-top: 20px; width: 400px !important; transform: translateX(-15%) translateY(-30%) !important;">
                    <h4 class="feature-version-free">FREE</h4>
                    <h4>התאמת עמוד Checkout:</h4>
                    <p>
                        באפשרותכם להוסיף או להסיר את השדות הבאים בעמוד ה-Checkout כדי לקבל מידע נוסף מהלקוחות. 
                        ניתן לבחור אילו שדות יוצגו ואילו יוסתרו בהתאם לצרכים שלכם.
                    </p>
                    <p>
                        השדות הזמינים הם:
                        <ul>
                            <li><strong>שדות עיר ורחוב מתקדמים:</strong> החלפת שדות העיר והרחוב הסטנדרטיים של WooCommerce בשדות Dropdown. בחירה בעיר מתוך הרשימה תאפשר גישה לכל הרחובות הזמינים באותה עיר בשדה הרחוב, לנוחות משתמש מקסימלית. אפשרות זו תמיד מופעלת ולא ניתנת לכיבוי.</li>
                            <li><strong>מספר בית:</strong> מאפשר ללקוח להזין את מספר הכניסה לבניין. אפשרות זו תמיד מופעלת ולא ניתנת לכיבוי</li>
                            <li><strong>שדה כניסה נגלל:</strong> מאפשר ללקוח להזין את אות הכניסה לבניין, למשל <code>א</code> או <code>ד</code>. אפשרות זו תמיד מופעלת ולא ניתנת לכיבוי
                            <span>שימו לב!</span><span>שדה כניסה עוזר בקבלת מיקוד </span>
                            </li>
                            <li><strong>מספר קומה:</strong> מאפשר ללקוח להזין את הקומה בה הוא נמצא.<br>שדה זה מאפשר לחשב עלויות משלוח נוספות מעל קומה מסויימת.<br>הגדרות משלוח לפי קומה נמצאות מטה.</li>
                            <li><strong>מספר דירה:</strong> מאפשר ללקוח להזין את מספר הדירה.</li>
                            <li><strong>קוד בניין:</strong> מאפשר ללקוח להזין את קוד הבניין (עבור השליח).</li>
                        </ul>
                    </p>
                    <p>
                        פשוט סמנו את תיבות הסימון (Checkbox) עבור השדות שברצונכם להפעיל, והשדות יתווספו אוטומטית לעמוד ה-Checkout. 
                        אם תבטלו את הבחירה, השדות יוסרו.
                    </p>

                </div> 
            </div>
        </div>';
            
        echo '<p style="font-family: Fredoka, sans-serif;font-size: 17px">שימו לב: שדות עיר ורחוב מופעלים אוטומטית.</p>';

        echo '<div class="info-row-lable">
            <div class="fields-setting-row" >
                <p class="lable-style"">שנה שדות לעיר ורחוב נגללים:</p>
            </div>
            <input type="checkbox" class="checkbox-style" checked disabled />
        </div>';
        
        echo '<div class="info-row-lable">
            <div class="fields-setting-row"">
                <p class="lable-style"">הוסף שדה מספר בית:</p>
            </div>
            <input  type="checkbox" class="checkbox-style" disabled checked />
        </div>';

        echo '<div class="info-row-lable">
            <div class="fields-setting-row"">
                <p class="lable-style"">הוסף שדה כניסה נגלל:</p>
            </div>
            <input  type="checkbox" class="checkbox-style" disabled checked />
        </div>';

        echo '<div class="info-row-lable">
            <div class="fields-setting-row"">
                <p class="lable-style"">הוסף שדה מספר דירה:</p>
            </div>
            <input id="show-checkout-aprtment-num-checkbox" type="checkbox" class="checkbox-style" '.($user_options['apartment_field_active']?'checked':'').'/>
        </div>';

        echo '<div class="info-row-lable">
            <div class="fields-setting-row" >
                <p class="lable-style"">הוסף שדה מספר קומה:</p>
            </div>
            <input id="show-checkout-floor-checkbox" type="checkbox" class="checkbox-style" '.($user_options['floor_field_active']?'checked':'').' />
        </div>';


        echo '<div class="info-row-lable">
            <div class="fields-setting-row"">
                <p class="lable-style"">הוסף שדה קוד בניין:</p>
            </div>
            <input id="show-checkout-entrance-code-checkbox" type="checkbox" class="checkbox-style" '.($user_options['building_code_field_active']?'checked':'').'/>
        </div>';



       
        // plugin settings
        echo '
        <div class="header-with-info">
            <h2 class="h2-style">הגדרות תוסף</h2>
        </div>';
        
        
        // send diagnostics
        echo '<div id="send-diagnostics-checkbox" class="info-row-lable">
        <div class="fields-setting-row">
            <p class="lable-style">שליחת דו"ח שגיאות:</p>
        </div>
        <input id="send-diagnostics-checkbox" type="checkbox" class="checkbox-style" '.($user_options['send_diagnostics_active']?'checked':'').'/>

        <div class="info-icon-container">
            '.$info_svg_icon.'
            <div class="info-window" style="padding-top: 20px;width: 400px !important; transform: translateX(-15%) translateY(-70%) !important;">
                <h4>שליחת דו"ח שגיאות:</h4>
                <p>
                    אפשרות זו מאפשרת לפלאגין לשלוח באופן אוטומטי נתוני שגיאות במידה ומתרחשות. 
                    באמצעות שליחת נתונים אלו, תעזרו לנו לשפר את הפלאגין ולספק חוויית שימוש טובה יותר.
                </p>
                <p>
                    כל נתוני השגיאות שנשלחים מוצפנים באופן מאובטח ונשמרים בשרתי <span style="color:cornflowerblue">Marvel Software Solutions</span>. 
                    המידע המשודר לא יכלול פרטים אישיים או סודיים, אלא רק נתוני דיאגנוסטיקה שמטרתם לשפר את ביצועי הפלאגין.
                </p>

                <h4 style="margin-top: 10px;">איך זה עוזר?</h4>
                <p>
                    נתוני השגיאות שנשלחים מאפשרים לנו לזהות בעיות מוקדם יותר, להבין כיצד הפלאגין מתפקד בסביבות שונות, ולשחרר עדכונים ותיקונים במהירות רבה יותר.
                </p>

                <h4 style="margin-top: 10px;">שימו לב!</h4>
                <p>
                    חשוב לזכור: המידע שנאסף ונשלח הוא דיאגנוסטי בלבד ולא כולל מידע פרטי או אישי. 
                    במידה ואינכם מעוניינים בשימוש באפשרות זו, תוכלו לבטל אותה.
                </p>
                <p>
                    תודה על שיתוף הפעולה שעוזר לנו להמשיך לשפר את המוצר עבורכם!
                </p>


            </div>
        </div>
        </div>';
        



        echo '
        <div class="header-with-info">
            <h2 class="h2-style">הגדרות הודעות מהירות</h2>
        </div>';


        echo '<div class="info-row-lable">
        <div class="fields-setting-row"">
            <p class="lable-style"">הפעלת הודעות מהירות:</p>
        </div>
        <input id="fast-comments-checkbox" type="checkbox" class="checkbox-style" '.($user_options['fast_msgs_active']?'checked':'').'/>
        <div class="info-icon-container">
            '.$info_svg_icon.'
                <div class="info-window" style="padding-top: 20px;width: 400px !important; transform: translateX(-15%) translateY(-80%) !important;">
                    <h4 class="feature-version-mixed">Mixed</h4>

                    <h4>פיצ\'ר הודעות מהירות להזמנה:</h4>
                    <p>
                        הפיצ\'ר מאפשר ללקוחות להוסיף בקלות הודעות מהירות להזמנה בעמוד ה-Checkout. 
                        הודעות אלו מסייעות למשלוח מדויק ולחוויית משתמש (UX) טובה יותר ברכישה.
                    </p>
                    <h4 style="margin-top:10px">איך זה עובד?</h4>
                    <p>
                        בעת ביצוע ההזמנה, המשתמש יוכל לבחור מתוך רשימת הודעות מהירות בלחיצת כפתור.<br>
                        לדוגמה:
                    </p>
                    <ul>
                        <li>נא לא לצלצל בפעמון</li>
                        <li>יש כלב בחצר</li>
                        <li>אנא השאירו את החבילה ליד הדלת</li>
                    </ul>
                    <h4 style="margin-top:10px">גרסת FREE:</h4>
                    <p>
                        בגרסת החינם ניתן להציג או להסתיר הודעות גנריות קבועות מראש, המתאימות לרוב המשתמשים.<br>
                        לדוגמה:
                        <ul>
                            <li>נא להשאיר את החבילה ליד הדלת.</li>
                            <li>בבקשה לא לדפוק או לצלצל בפעמון.</li>
                        </ul>
                    </p>
                    <h4 style="margin-top:10px">גרסת <span style="color:green">PRO</span> + <span style="color:goldenrod">Ultimate</span>:</h4>
                    <p>
                        בגרסאות אלו תוכלו להגדיר כמה הודעות מותאמות אישית שתרצו, בהתאם לצרכים של העסק שלכם.<br>
                        כך תוכלו לשפר את חוויית הקנייה עבור הלקוחות שלכם!
                    </p>
                </div>

            </div>
        </div>';




        echo '<div class="comments-overlay-container '.($user_options['fast_msgs_active']?'':'disabled').' ">
                <div class="comments-container">
                    <h2 class="h2-style" style="margin-top: 10px; font-size: 20px">טבלאת הודעות מהירות:</h2>
                    

                    <div class="comments-table">
                        <table>
                            <thead>
                                <tr>
                                    <th id="th-msg" style="width: 80% !important">הודעות (קבועות)</th>
                                </tr>
                            </thead>
                            <tbody id="comments-list">
                    ';

                    // Dynamically generate rows for each message
                    if (!empty($messages)) {
                        foreach ($messages as $message) {
                            echo '
                                <tr id="msg-' . htmlspecialchars($message->id) . '">
                                    <td>' . htmlspecialchars($message->msg_content) . '</td>
                                </tr>
                            ';
                        }
                    } else {
                        echo '
                            <tr>
                                <td colspan="2" style="text-align: center;">אין הודעות זמינות</td>
                            </tr>
                        ';
                    }

                    echo '
                        </tbody>
                    </table>
                </div>
            </div>
        </div>';

        echo '</div>';



        // ===============================================================
        // ===============================================================

        echo '<div class="left-settings-flex" >';

        



            // ==========================================================================================
            // FIRST WIN
            // ==========================================================================================

            echo '<div class="container active" id="city-container">
                    
            <div style="display: flex; align-items: center;">
                <h2 class="h2-style">הגדרת מחירים ידנית:</h2>
                <div class="info-icon-container">
                    '.$info_svg_icon.'
                    <div class="info-window" style="padding-top: 20px;width: 400px; padding-top: 15px; transform: translateX(-15%) translateY(-30%) !important;">
                        <h4 class="feature-version-free">FREE</h4>
                        <h4>הגדרת מחירים ידנית</h4>
                        <p>
                            באפשרותכם להגדיר מחירים ידנית לכל עיר בעזרת רשימה נוחה וידידותית:
                        </p>
                        <ul>
                            <li><strong>גלילה ברשימה:</strong> ניתן לגלול ברשימה ולהגדיר מחיר עבור כל עיר בצורה פרטנית.</li>
                            <li><strong>חיפוש ערים:</strong> השתמשו בשורת החיפוש כדי למצוא במהירות את העיר שברצונכם להגדיר לה מחיר.</li>
                            <li><strong>כפתור איפוס:</strong> בכל שורה מופיע כפתור חץ עגול. לחיצה עליו תאפס את המחיר למחיר האזור הגיאוגרפי.</li>
                        </ul>
                        <p>
                            <strong>מה קורה אם מאפסים כאשר מחיר האזור לא מוגדר?</strong><br>
                            אם מחיר האזור הגיאוגרפי לא מוגדר, המחיר יאופס לערך אפס (כלומר ללא תוספת תשלום עבור המשלוח).
                        </p>
                        <p>
                            <strong>עדיפות למחירים ידניים:</strong><br>
                            מחירים שמוגדרים ידנית מתבצעים בעדיפות על פני המחירים שהוגדרו לפי אזור גיאוגרפי. 
                            כלומר, אם לעיר מסוימת מוגדר גם מחיר גיאוגרפי וגם מחיר ידני, המחיר הידני הוא זה שיחושב בעמוד ה-Checkout.
                        </p>
                        <p>
                            פונקציונליות זו מאפשרת לכם שליטה פרטנית ומדויקת בניהול מחירי המשלוחים.
                        </p>
                    </div>                

                </div>
            </div>

            <div class="base-price-row">
                <label for="base-price-input" class="base-price-label">מחיר גלובלי:</label>
                <input type="text" id="base-price-input" placeholder="מחיר" />
                <p class="base-price-text" >ש"ח</p>
                <button id="set-global-price"  class="cool-button" style="height=52px !important; padding-left: 20px !important; padding-right: 20px !important">החל</button>
                <button id="clean-global-price-input" class="cool-button" style="height=52px !important; padding-left: 20px !important; padding-right: 20px !important">נקה</button>

                <div class="info-icon-container">
                '.$info_svg_icon.'
                    <div class="info-window" style="padding-top: 20px;width: 400px !important; transform: translateX(100%) translateY(0%) !important;">
                        <h4 class="feature-version-free">FREE</h4>
                        <h4>החלת מחיר גלובלי</h4>
                        <p>
                            פיצ\'ר "החלת מחיר גלובלי" מאפשר לכם להגדיר מחיר אחיד שיחול על כל היישובים ללא יוצא מן הכלל:
                        </p>
                        <ul>
                            <li><strong>קביעת מחיר כללי:</strong> באמצעות המחיר הגלובלי ניתן להחיל מחיר אחיד על כל היישובים ברשימה בלחיצת כפתור.</li>
                            <li><strong>אפשרות לשינויים ידניים:</strong> לאחר החלת המחיר הגלובלי, תוכלו להגדיר מחירים יוצאי דופן עבור יישובים ספציפיים בצורה ידנית.</li>
                            <li><strong>שילוב בין מחיר גלובלי ויוצאי דופן:</strong> המחיר הגלובלי מאפשר להגדיר מחיר כללי בקלות, ולאחר מכן לטפל באופן פרטני במקרים מיוחדים שבהם נדרש מחיר שונה.</li>
                        </ul>
                        <p>
                            <strong>שליטה מלאה וניהול חכם:</strong><br>
                            השילוב בין מחיר גלובלי למחירים ידניים מאפשר לכם גמישות ושליטה מלאה בניהול מחירי המשלוחים. כך תוכלו להגדיר מחיר כללי בקלות ובמהירות, ולדייק את המחירים לפי הצרכים שלכם.
                        </p>
                        <p>
                            פונקציונליות זו נועדה לייעל את ניהול מחירי המשלוחים שלכם תוך שמירה על פשטות ונוחות למשתמש.
                        </p>
                    </div>


                    </div>
                </div>
                <h4 class="h4-style" style="margin-top: 5px;">ניתן לחפש ערים ידנית ולהגדיר מחיר עבורם.</h4>

                <!-- Search Bar -->

                <div class="search-bar-row">
                    <input id="manual-city-search" type="text" class="search-bar" placeholder="חיפוש ערים...">
                    <button class="cool-button clean-button-style" style="height=52px !important; padding-left: 50px !important; padding-right: 50px !important">ניקוי חיפוש</button>
                </div>
                ';
                

            // SVG image for "no results"
            echo '<div class="no-results"  style="display: none;">';
                echo '
                    <img src="' . MARVELOUS_SHIPPING_URL . 'images/empty.svg" alt="No results">
                    <p>אין תוצאות</p>';
            echo '</div>';


            // Initialize a position counter

            // Start the scrollable container
            echo '<div class="rows-container">';

            // Iterate through the `$israel_heb2en` array
            foreach ($israel_heb2en as $city_heb => $city_en) {
                if (isset($cities_data[$city_heb])) {
                    $city_data = $cities_data[$city_heb];

                    // Fetch district and shipping price from `$cities_data`
                    $district = htmlspecialchars($city_data->district_heb_name ?? 'לא מסווג');
                    $shipping_price = htmlspecialchars($city_data->shipping_price ?? 'לא זמין');
                    $city_allowed = $city_data->city_allowed ? 'checked' : '';

                    // Render city row
                    echo '<div class="row" data-district="' . $district . '" data-city-heb="' . htmlspecialchars($city_heb) . '" data-city-en="' . htmlspecialchars($city_en) . '">';
                    echo '    <div class="city-name-and-district">';
                    echo '        <label for="city-price-' . htmlspecialchars(str_replace(' ', '_', $city_en)) . '">' . htmlspecialchars($city_heb) . ':</label>';
                    echo '        <p>' . $district . '</p>'; // Display the district dynamically
                    echo '    </div>';
                    echo '    <div class="city-price-and-ILS">';
                    echo '        <input class="price-input" style="width: 50% !important;" type="text" id="city-price-' . htmlspecialchars(str_replace(' ', '_', $city_en)) . '" placeholder="מחיר משלוח" value="' . $shipping_price . '">';
                    echo '        <p>ש"ח</p>';
                    echo '    </div>';
                    echo '    <div class="city-price-and-ILS">';
                    echo '        <input type="checkbox" class="allow-region-checkbox" id="' . htmlspecialchars(str_replace(' ', '_', $city_en)) . '-allow-city" ' . $city_allowed . ' />';
                    echo '        <label for="' . htmlspecialchars($city_en) . '-allow-city" class="allow-region-checkbox-text">פעיל</label>';
                    echo '    </div>';
                    echo '</div>';
                } else {
                    // Render city row
                    echo '<div class="row" data-district="לא מסווג" data-city-heb="' . htmlspecialchars($city_heb) . '" data-city-en="' . htmlspecialchars($city_en) . '">';
                    echo '    <div class="city-name-and-district">';
                    echo '        <label for="city-price-' . htmlspecialchars(str_replace(' ', '_', $city_en)) . '">' . htmlspecialchars($city_heb) . ':</label>';
                    echo '        <p>לא מסווג</p>'; // Display the district dynamically
                    echo '    </div>';
                    echo '    <div class="city-price-and-ILS">';
                    echo '        <input class="price-input" style="width: 50% !important;" type="text" id="city-price-' . htmlspecialchars(str_replace(' ', '_', $city_en)) . '" placeholder="מחיר משלוח" value="0">'; // TODO: price here is always 0 fix it $shipping_price
                    echo '        <p>ש"ח</p>';
                    echo '    </div>';
                    echo '    <div class="city-price-and-ILS">';
                    echo '        <input type="checkbox" class="allow-region-checkbox" id="' . htmlspecialchars(str_replace(' ', '_', $city_en)) . '-allow-city" checked />'; //TODO: always show checkeed here fix this!
                    echo '        <label for="' . htmlspecialchars($city_en) . '-allow-city" class="allow-region-checkbox-text">פעיל</label>';
                    echo '    </div>';
                    echo '</div>';

                }
            }


            echo '</div>';
            echo '</div>';



            // ================================================================================
            // chartJS 
            // ================================================================================
            $desired_order = [
                "רמת הגולן",
                "מחוז צפון",
                "מחוז חיפה",
                "מחוז מרכז",
                "תל אביב - יפו",
                "יהודה ושומרון",
                "מחוז ירושלים",
                "דרום עליון",
                "דרום תחתון"
            ];
            
            $chart_data = countOrdersByDistrict();
            $ordered_data = [];
            
            // Sort the data based on the desired order
            foreach ($desired_order as $district) {
                if (isset($chart_data[$district])) {
                    $ordered_data[$district] = $chart_data[$district];
                }
            }
            
            // Format the data into the desired structure
            $formatted_data = [];
            foreach ($ordered_data as $district => $count) {
                $formatted_data[] = $district;
                $formatted_data[] = $count;
            }
            
            $data_res = implode(",", $formatted_data);


            // ===================================================================
            // import button and header
            // ===================================================================
            echo '<div class="import-order-log-flex">';

            echo '<h2 class="h2-style" style="margin-bottom:0;">התפלגות המשלוחים שלי לפי אזורים</h2>';
 
            echo '</div>';

            // ===================================================================

            echo '<canvas id="deliveryPieChart" width="200" height="200" class="doughnut-chart"></canvas>';
            echo '<p id="districts-chart-data" style="display:none;">'.$data_res.'</p>';

            echo '</div>';


            echo '</div>';


            // Code that may throw an exception
        } catch (\Throwable $th) {
            logException($th, __FUNCTION__);
        }
    }

// =======================================================================================
// =======================================================================================
?>
