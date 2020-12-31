<?php
/**
 * @package Booking_ortoped
 * @version 1.0
 */
/*
Plugin Name: Ortoped Booking Calendar
Description: Plugin for site www.ortopedplus.com Booking doctor visit.
Author: volynshchiov@gmail.com
Version: 1.0
*/

// Add new submenu Options
function orto_add_admin(){
    add_options_page('Плагин бронирования на прием',
                     'Календарь записи', 8, 'booking-admin', 'orto_booking_admin_page' );
}

//Admin page views and forms
function orto_booking_admin_page(){
  echo "<h2> Настройки календаря бронирования.</h2>";

  echo "<h3> Управление адресами </h3>";
  admin_adress_control();

  echo "<h3> Управление специалистами </h3>";
  admin_doctor_control();

  echo "<h3> Управление слотами </h3>";
  admin_slot_control();

//   echo "<h3> Управление записями </h3> ";
//   admin_reserv_control();

  echo "<h3> URL страницы 'Готово'";
  admin_url_thanks_page();
}

function admin_url_thanks_page(){

    if (isset($_POST['admin_change_url_btn'])){
        if(!current_user_can('manage_options')){
            exit("Недостаточно прав");
        }
        $booking_thanks_url = $_POST['booking_thanks_url'];
        update_option('booking_thanks_url', $booking_thanks_url);
    }

    // view
    echo
	"
		<form name='booking_thanks_url_form' method='post' action='".$_SERVER['PHP_SELF']."?page=booking-admin&amp;updated='true'>
        <input type='text' name='booking_thanks_url' value='".get_option('booking_thanks_url')."' >
        <input type='submit' name='admin_change_url_btn' value='Изменить URL' />
        </form>
    ";

}

function admin_adress_control(){
    global $wpdb;
    $adress_exist = get_all_adress();

    if (isset($_POST['admin_add_adress_btn'])){
        if(!current_user_can('manage_options')){
            exit("Недостаточно прав");
        }
        $adress = $_POST['admin_adress_form'];

        $wpdb->insert($wpdb->prefix.orto_adress, array(
            "adress" => $adress
        ));
    }

    if (isset($_POST['admin_add_email_btn']) && isset($_POST['adress_to_update'])){

        if(!current_user_can('manage_options')){
            exit("Недостаточно прав");
        }

        $id = $_POST['adress_to_update'];
        $email = $_POST['admin_adress_form'];

        $wpdb->insert($wpdb->prefix.orto_email,
            array("adressID" => $id,
                  "email" => $email));
    }

    // view
    echo
    "
    <form name='admin_adress' method='post' action='".$_SERVER['PHP_SELF']."?page=booking-admin&amp;updated='true'>

    <select name='adress_to_update'>
    <option></option>";

    foreach ($adress_exist as $adr){
        echo "<option value='{$adr->id_adress}'> {$adr->adress} </option>";
    }

    echo
    "</select>

    <input type='text' name='admin_adress_form' >
    <input type='submit' name='admin_add_email_btn' value='Добавить e-mail'/>
    <input type='submit' name='admin_add_adress_btn' value='Добавить адрес' />
    </form>";

}

function admin_doctor_control(){

    global $wpdb;
    $adress_exist = get_all_adress();

    if (isset($_POST['admin_add_doctor_btn'])){

        $name = $_POST['admin_doctor_name_input'];
        $specialty = $_POST['admin_doctor_specialty_input'];

        $wpdb->insert($wpdb->prefix.orto_doctor, array(
            "specialty" => $specialty,
            "doctor_name" => $name,
            "adressID" => $_POST['doctor_adress_id']

        ));
    }

    // html view
    echo "
    <form name='admin_doctor' method='post' action='".$_SERVER['PHP_SELF']."?page=booking-admin&amp;updated='true'>

        <p> Адрес специалиста:
        <select name='doctor_adress_id'>";

            foreach ($adress_exist as $adr){
                echo "<option value='{$adr->id_adress}'> {$adr->adress} </option>";
            }

        echo "
        </select> </p>
        <p> Специальность:
        <input type='text' name='admin_doctor_specialty_input' >  </p>
        <p> Имя:
        <input type='text' name='admin_doctor_name_input' > </p>
        <p></p>
        <input type='submit' name='admin_add_doctor_btn' value='Добавить врача' />
        <input type='submit' name='admin_delete_doctor_btn' value='Удалить врача' />
        </form>";
}

function admin_slot_control(){

    global $wpdb;
    $doctors = get_all_doctors();

    if (isset($_POST['admin_slot_btn'])) {
        $doctorID = $_POST['doctor_slot_id'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $min_step = $_POST['min_step'];
        $slot_role = $_POST['slot_role'];

       $tmp_date = $start_date;

       $time_points = array();
        for ($cur_time = strtotime($start_time); $cur_time <= strtotime($end_time); $cur_time += ($min_step*60)){
            $time_points[] = $cur_time;
        }


       for ($start = strtotime($start_date); $start <= strtotime($end_date); $start += 24*60*60){

            foreach($time_points as $time){
                $wpdb->insert($wpdb->prefix.orto_slot, array(
                    'doctorID'=>$doctorID,
                    'date'=> date('Y-m-d', $start),
                    'time'=> (($time/3600) %24) . ":" . (($time/60) % 60) . ":00",
                    'role'=> $slot_role

                ));

            }
       }
    }

    if (isset($_POST['admin_slot_stop_btn'])){

        $doctorID = $_POST['doctor_slot_id'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];


        $query = $wpdb->prepare("SELECT id_slot, doctorID, date, time, available
        FROM {$wpdb->prefix}orto_slot
        WHERE
        doctorID = %d AND
        date BETWEEN %s AND %s
        AND time BETWEEN %s AND %s ", $doctorID, $start_date, $end_date, $start_time, $end_time);

        $stop_list = $wpdb->get_results($query);


        foreach ($stop_list as $stop_slot){
            $wpdb->update($wpdb->prefix.orto_slot,
                array('available' => 0 ),    // set 'available' to false
                array('id_slot' => $stop_slot->id_slot) // where id_slot
            );

        }
        if (isset($_POST['stop_comment'])){
            $comment = $_POST['stop_comment'];
            $wpdb->insert($wpdb->prefix.orto_comment,
            array('date'=>$stop_slot->date, 'comment'=>$comment));
        }


    }

    // html view
    $roles = wp_roles()->get_names();
    $nul = null;
    echo
    "
    <form name='admin_slot' method='post' action='".$_SERVER['PHP_SELF']."?page=booking-admin&amp;updated='true'>

    Специалист: <select name='doctor_slot_id'>";

    foreach ($doctors as $doc){
        echo "<option value='{$doc->id_doctor}'> {$doc->adress} ({$doc->specialty} ) </option>";
    }

    echo "</select>

      <p> Дата начала: <input type='date' name='start_date' >
          Дата окончания: <input type='date' name='end_date' >
      </p>

       <p> Стартовое время: <input type='time' name='start_time' >
           Конечное время: <input type='time' name='end_time' >
           Интервал минут: <input type='text' name='min_step'>
      </p>
      <p>
      Группа: <select name='slot_role'>
      <option value='{$role}'> {$nul} </option>  ";
      foreach( $roles as $role ) {
        echo "<option value='{$role}'> {$role} </option>" ;
    }
    echo "</select></p>
     <p> Причина стопа: <input type='text' name='stop_comment'></p>
     <p><input type='submit' name='admin_slot_btn' value='Добавить слот' /> <input type='submit' name='admin_slot_stop_btn' value='Добавить стоп' /> </p>
    ";
}


function add_reservation($slotID, $name, $adress, $email, $phone, $age){
    global $wpdb;
    $query = $wpdb->prepare("SELECT available FROM {$wpdb->prefix}orto_slot WHERE id_slot= %d", $slotID);
    $avail = $wpdb->get_var($query);
        if($avail == 0){
            return false;
        }
        if ($wpdb->insert($wpdb->prefix.orto_reserv, array(
            'slotID'=> $slotID,
            'name'=> $name,
            'adress'=> $adress,
            'email'=> $email,
            'phone'=> $phone,
            'age'=> $age))) {

                $wpdb->update($wpdb->prefix.orto_slot,
                array('available' => false),
                array('id_slot' => $slotID));

                return true;

        }
        return false;
}

function get_all_doctors(){
    global $wpdb;
    return $wpdb->get_results("SELECT id_doctor, specialty, doctor_name, adress
                                         FROM {$wpdb->prefix}orto_doctor
                                         INNER JOIN {$wpdb->prefix}orto_adress
                                         ON adressID = id_adress");
}

function get_all_adress(){
    global $wpdb;
    return $wpdb->get_results("SELECT id_adress, adress FROM {$wpdb->prefix}orto_adress");
}

function get_available_slots_for_date($doctorID, $date, $user_role){
    global $wpdb;
    // $add_days = get_last_book_day();
    // $d = new DateTime(null, new DateTimeZone('Europe/Moscow'));
    // $d->modify('+'. $add_days . ' day');
    // $last_day = $d->format('Y-m-d');
    $last_day = get_last_book_day();

    $query = $wpdb->prepare("SELECT time FROM {$wpdb->prefix}orto_slot
                             WHERE doctorID=%d AND date=%s
                             AND date<=%s 
                             AND role=%s AND available=1
                             ORDER BY time", $doctorID, $date, $last_day, $user_role );
    return $wpdb->get_col($query);
}

function get_slot_id($doctorID, $date, $time, $status=1){
    global $wpdb;
    $sql = $wpdb->prepare("SELECT id_slot FROM {$wpdb->prefix}orto_slot
                        WHERE doctorID=%d AND date=%s AND time=%s AND available=%d", $doctorID, $date, $time, $status);
    return $wpdb->get_var($sql);

}

function get_available_calendar_dates(){
    global $wpdb;
    $calendar_doc_data = get_all_doctors();
    $res=array();
    // $today = date('Y-m-d', time());
    // $two_month_forward = date('Y-m-d', mktime(0,0,0, date('m')+1, date('d'), date('Y')));

    $last_day = get_last_book_day();
    $first_day = get_first_book_day();

    foreach( $calendar_doc_data as $doc ){
    $time_query = $wpdb->get_col("SELECT DISTINCT UNIX_TIMESTAMP(date) FROM {$wpdb->prefix}orto_slot
                                            WHERE doctorID={$doc->id_doctor}
                                            AND available=1
                                            AND date >= '{$first_day}'
                                            AND date <= '{$last_day}'");

    $res[$doc->id_doctor] = $time_query;

    }
    return $res;
}



function booking_process(){

    if(isset($_POST['book_doctor'])&&
       isset($_POST['book_date']) &&
       isset($_POST['book_time']) &&
       isset($_POST['book_client_name']) &&
       isset($_POST['book_client_email']) &&
       isset($_POST['book_client_age']) &&
       isset($_POST['book_phone'])) {

            $book_doctor = $_POST['book_doctor'];
            $book_date = $_POST['book_date'];
            $book_time = $_POST['book_time'];
            $book_client_name = $_POST['book_client_name'];
            $book_home_adress = $_POST['book_home_adress'];
            $book_client_email = $_POST['book_client_email'];
            $book_client_age = $_POST['book_client_age'];
            $book_phone = $_POST['book_phone'];

            $slotID = get_slot_id($book_doctor, $book_date, $book_time);

        // if sql insert 'ok' - Send e-mail and redirect
        if (add_reservation($slotID,
                            $book_client_name,
                            $book_home_adress,
                            $book_client_email,
                            $book_phone,
                            $book_client_age)){
            global $wpdb;

            $doc_data = $wpdb->get_row($wpdb->prepare("SELECT DISTINCT id_adress, adress, specialty
                                                    FROM {$wpdb->prefix}orto_adress INNER JOIN {$wpdb->prefix}orto_doctor
                                                    ON id_adress = adressID
                                                    WHERE id_doctor = %d", $book_doctor));

            $emails = $wpdb->get_col("SELECT DISTINCT email FROM {$wpdb->prefix}orto_email
                                    WHERE adressID=$doc_data->id_adress");


            $f_book_date = mysql2date('d.M.Y',$book_date);

            $subject =  "Запись по адресу {$doc_data->adress}";
            $message = "Новая запись на прием в салон по адресу: {$doc_data->adress} \n
                        Специалист: {$doc_data->specialty} \n
                        Дата: {$f_book_date}  \n
                        Время: {$book_time} \n
                        \n
                        Контактные данные: \n
                        ФИО: {$book_client_name}  \n
                        Возраст (полных лет): {$book_client_age} \n
                        Домашний адрес: {$book_home_adress}  \n
                        Тел: {$book_phone} \n";

            wp_mail($emails, $subject, $message);

            $client_subj = "Запись на прием к специалисту: {$doc_data->specialty}";
            $client_message = "Уважаемый(ая), {$book_client_name} \n
            Спасибо, что записались на прием в ортопедический салон по адресу: {$doc_data->adress} \n
            Дата и время приема: {$f_book_date} {$book_time} \n
            При себе необходимо иметь:
            1. Копию полиса.
            2. Копию свидетельства о рождении или паспорта.
            3. Медицинскую карту. Обязательно
            4. Пеленку.";
            $head = "From: Ортопедия Плюс <wordpress@ortopedplus.com>";

            wp_mail($book_client_email, $client_subj, $client_message, $head);


            if (get_option('booking_thanks_url')){
                $url = get_option('booking_thanks_url');
                echo "<script type='text/javascript'> window.location.replace('$url'); </script>";


            }



        } else {
            echo "<h1 style='color:red'>При отправке записи возникла ошибка.</h1>
            <p> Возможно расписание не актуально. Попоробуйте еще раз... </p>";
            unset($_POST['book_date']);
            unset($_POST['book_time']);

            // wp_die("<h1>При отправке записи возникла ошибка.</h1>", "<p> Возможно расписание не актуально. Попоробуйте еще раз... </p>");
        }
  }
}


function orto_form_view(){
    $doctors = get_all_doctors();
    // $adress = get_all_adress();
    $site_domain = get_home_url();
    $user_role = get_current_user_role();

    echo
    "
    <p align='right'>
    <a align='right' href={$site_domain}/wp-login.php?redirect_to={$_SERVER['REQUEST_URI']}>ЦРБ Усть-Абакан</a>
    </p>
    <p><form method='post' id='booking_form' action='' >

    <p><label> Выберите адрес (и специалиста): <br> <select id='book_doctor_select' name='book_doctor' ;>
    <option value=''></option>";
    foreach ($doctors as $doc){
        echo "<option value='{$doc->id_doctor}'> {$doc->adress} ({$doc->specialty} ) </option>";
    }
    echo
    " </select></label></p>
    <p>
    <label> Выберите дату:
    <div id='datepicker'></div> </label>
    </p>
    <p><input type='hidden' id='date_field' name='book_date' value=''></p>
    <label> Выберите время:
    <div id='time_block'></div> </label>
    <p></p>
    ";


    datepicker_js();

    echo
    "
    <p>
    <label> ФИО пациента*<br>
    <input type='text' name='book_client_name' required='true'>
    </label></p>

    <p><label> Адрес проживания<br>
    <input type='text' name='book_home_adress'>
    </label></p>

    <p><label> Ваш e-mail* <i style='font-weight: 100'>(Например: example@email.com)</i><br>
    <input type='email' name='book_client_email' required='true'>
    </label></p>

    <p><label> Возраст пациента* <i style='font-weight: 100'>(Полных лет)</i><br>
    <input type='number' name='book_client_age' required='true' value='0' min='0' max='18'>
    </label></p>

    <p><label> Контактный телефон* <i style='font-weight: 100'>(Например: +7(999)999-99-99)</i><br>
    <input type='tel' name='book_phone' placeholder='+7' value='+7' pattern='^((8|\+7)[\- ]?)?(\(?\d{3}\)?[\- ]?)?[\d\- ]{7,10}$' required='true'>
    </p>
    <p>
    <input type='checkbox' name='personal-data-agree' value='' required='true' checked> Согласен на обработку персональных данных
    </p>
    <p><input checked='checked' type='radio'> Бесплатно по ОМС (Кроме приема МСЭК)</p>
    ";
    echo apply_filters( 'cptch_display', '' );
    echo "
    <p><input type='button' name='book_form_submit' onclick='validate_form(this); event.returnValue=false; return false;' value='ЗАПИСАТЬСЯ'></p>
    <div id='warning'></div>
    <style type='text/css'>
        .ui-datepicker-calendar a.ui-state-default { 
            background-image: linear-gradient(#00653B, #00a862); 
        }   
    </style>
    ";
}


function init_validate(){
?>
<script type="text/javascript">
      function validate_form(form){

        if(document.getElementById('date_field').value === ''){
            warning.innerHTML = "<p></p><h1>Выберите дату и время!</h1>";
            return;
        }

        if(!jQuery("input[name='book_time']:checked").val()){
           warning.innerHTML = "<p></p><h1>Выберите дату и время!</h1>";
           return;
        }


        if(document.getElementsByName("book_doctor").length !== 1 ){
           warning.innerHTML = "<p></p><h1>Ошибка при выборе адреса специалиста!</h1>";
           return;
        }

        let book_client_name = document.getElementsByName("book_client_name")[0].value;
        book_client_name =  book_client_name.trim();
        if(book_client_name === '') {
           warning.innerHTML = "<p></p><h1>Заполните поле: ФИО пациента</h1>";
           return;
        }


        var r_chmail = /.+@.+\..+/i;
        if(!r_chmail.test(document.getElementsByName("book_client_email")[0].value)){
            warning.innerHTML = "<p></p><h1>Не верный e-mail</h1>";
            return;
        }

        if(!document.getElementsByName('book_client_age')[0].value){
            warning.innerHTML = "<p></p><h1>Заполните поле: Возраст пациента (полных лет)</h1>";
            return;
        }

        var r_phone = /^((8|\+7)[\- ]?)?(\(?\d{3}\)?[\- ]?)?[\d\- ]{7,10}$/;
        if(!r_phone.test(document.getElementsByName("book_phone")[0].value)){
            warning.innerHTML = "<p></p><h1>Не верный телефон</h1>";
            return;
        }

        document.getElementById('booking_form').submit();
      }
    </script>
<?php
}



// Customer business needs date + 3 working days

// function get_first_book_day(){
//     $date =getdate();
//     $wday = $date['wday'];

//     switch ($wday) {
//         case 1:
//         case 2:
//             return +3;
//         case 3:
//         case 4:
//         case 5:
//         case 6:
//             return +5;
//         case 0:
//             return +4;
//     }
// }

function get_first_book_day(){
    return date('Y-m-d', strtotime("+3 weekday"));
}

// function get_last_book_day(){
//     $date =getdate();
//     $wday = $date['wday'];

//     if($wday > 0 && $wday < 6){
//         return +7;
//     }
//     elseif ($wday == 6){return +9;}
//     elseif ($wday == 0){return +8;}

// }
function get_last_book_day(){
    return date('Y-m-d', strtotime("+5 weekday"));
}


/**
 * скрипт выбора даты datepicker

 */
function datepicker_js(){
	// подключаем все необходимые скрипты: jQuery, jquery-ui, datepicker
	wp_enqueue_script('jquery-ui-datepicker');

	// подключаем нужные css стили
	wp_enqueue_style('jqueryui', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.css', false, null );

	// инициализируем datepicker
	add_action('wp_footer', 'init_datepicker', 99 );

	function init_datepicker(){
        
        $start_date = get_first_book_day();
        $fin_date = get_last_book_day();
        $available_calendar_dates =  json_encode(get_available_calendar_dates());


	?>
		<script type="text/javascript">

        var start = '<?php echo $start_date;?>';
        var stop ='<?php echo $fin_date;?>';
        var available_calendar_dates = JSON.parse('<?php echo $available_calendar_dates; ?>');

		jQuery(document).ready(function($){
			'use strict';
			// настройки по умолчанию. Их можно добавить в имеющийся js файл,
			// если datepicker будет использоваться повсеместно на проекте и предполагается запускать его с разными настройками
			$.datepicker.setDefaults({
                closeText: 'Закрыть',
				prevText: '<Пред',
				nextText: 'След>',
				currentText: 'Сегодня',
				monthNames: ['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'],
				monthNamesShort: ['Янв','Фев','Мар','Апр','Май','Июн','Июл','Авг','Сен','Окт','Ноя','Дек'],
				dayNames: ['воскресенье','понедельник','вторник','среда','четверг','пятница','суббота'],
				dayNamesShort: ['вск','пнд','втр','срд','чтв','птн','сбт'],
				dayNamesMin: ['Вс','Пн','Вт','Ср','Чт','Пт','Сб'],
				weekHeader: 'Нед',
				// dateFormat: 'dd-mm-yy',
				firstDay: 1,
				showAnim: 'slideDown',
				isRTL: false,
				showMonthAfterYear: false,
				yearSuffix: ''
			} );

			// Инициализация
			$('#datepicker').datepicker({
                inline: true,
                // minDate: new Date('2019-07-26'),
                minDate: start,
                // maxDate: new Date('2019-07-30'),
                maxDate: stop,
                // dateFormat: 'dd/mm/yy',
                dateFormat: 'yy-mm-dd',
                beforeShowDay: noShowDays,
                altField: '#date_field' });

                function noShowDays(date){
               
                    var noWeekend = $.datepicker.noWeekends(date);
                    var id_doc_calendar = jQuery('#book_doctor_select').val();
                    
                    var check_date = available_calendar_dates[id_doc_calendar] ? available_calendar_dates[id_doc_calendar] : [];
                    check_date = check_date.map(function(item, index, array){
                        item = item * 1000;       // to milliseconds
                        return item - 3600000*4;  // minus 4 hours
                                                  //BECAUSE mySQL return date at 04:00 GMT+07
                    });

                      if(noWeekend[0]){

                        if(check_date.includes(Date.parse(date))){
                            
                            return [true]
                        } else {
                            // console.log(Date.parse(date));
                            return [false]
                        }

                      } else {
                          return noWeekend;
                          }

                }

        });
		</script>
    <?php
    }
}

function show_slots_javascript(){
    ?>
    <script type="text/javascript">

    jQuery(document).ready(function($){

        var t = jQuery('#date_field').val();
        var ajax_url = '/wp-admin/admin-ajax.php';


    $('#book_doctor_select').change(function(){
        jQuery('#datepicker').datepicker('refresh');
        reload_calendar();
    });

    $('#datepicker').change(function(){
        event.preventDefault();
        reload_calendar();

        });


    function reload_calendar(){
    $('#time_block').empty();
    jQuery.get( ajax_url, {  'action': 'show_slots',
                             'book_date': jQuery('#date_field').val(),
                             'doctorID': jQuery('#book_doctor_select').val()
      }, function(response) {

            let items = JSON.parse(response);

            let time_table = jQuery('<table/>', {
                id: 'time'
            }).appendTo("#time_block");

            if ($.isEmptyObject(items)) {
                 jQuery('#time_block').append('<h4>Все приемные часы заняты. Выберите другую дату! <br>Следующий день для записи открывается каждый рабочий день в 07ч.00мин. Не считая выходных.</h4>');
                }
            $('#time').remove();
            time_table = jQuery('<table/>', {
                id: 'time'
            }).appendTo("#time_block");

            var time_table_body = jQuery('<tbody/>', {id: 'time_tbody' });
            jQuery(time_table_body).appendTo(time_table);

            for(var i=0; i<items.length; i++){

                if(i%3===0){
                    var tr = jQuery('<tr/>');
                    jQuery(tr).appendTo(time_table_body);
                }
                add_time_row(items[i]);
            }

            function add_time_row(item){
                jQuery('<td/>').append(
                        jQuery('<label/>').append(
                            jQuery('<input/>', {
                                type: 'radio',
                                name: 'book_time',
                                val: item
                            })).append(item.slice(0,-3))).appendTo('#time_tbody');

            }

         });
         jQuery.get(ajax_url, { 'action': 'get_date_comment',
                                'book_date': jQuery('#date_field').val()

                 }, function(response){
                    if (response != 0){

                        let html = '<h4> ' + response + '</h4>';
                        jQuery('#time_block').html(html);
                    }
                 });
      }

    });
    </script>
    <?php
    }

function my_login_redirect( $redirect_to, $request, $user  ) {
    if (is_array( $user->roles ) && in_array( 'doctor', $user->roles )){
        return site_url() . '/zapis-na-priem-new';
    }
    return ( is_array( $user->roles ) && in_array( 'administrator', $user->roles ) ) ? admin_url()  : site_url();
}
add_filter( 'login_redirect', 'my_login_redirect', 10, 3 );

function orto_booking_run(){

// Booking logic
    add_role( 'doctor', __('Doctor'),array('read' => true));
    orto_form_view();
    booking_process();
}


if (wp_doing_ajax()){
    add_action( 'wp_ajax_nopriv_show_slots', 'show_slots_callback' );
    add_action( 'wp_ajax_show_slots', 'show_slots_callback' );
    add_action( 'wp_ajax_nopriv_get_date_comment', 'get_date_comment_callback' );
    add_action( 'wp_ajax_get_date_comment', 'get_date_comment_callback' );
}
add_action('wp_footer', 'show_slots_javascript', 99);
// add_action('wp_footer', 'get_date_comment_javascript', 99);

function show_slots_callback(){
    $date = $_GET['book_date'];
    $doctorID = empty($_GET['doctorID']) ? 1 : $_GET['doctorID'];
    $user_role = get_current_user_role();
    $slots = get_available_slots_for_date($doctorID, $date, $user_role);
    echo json_encode($slots);

    wp_die();
}

function get_date_comment_callback(){
    global $wpdb;
    $date = $_GET['book_date'];
    $comment = $wpdb->get_var($wpdb->prepare("SELECT comment FROM {$wpdb->prefix}orto_comment
                                              WHERE date= %s", $date));
    echo $comment;
    wp_die();
}


function get_current_user_role(){
    global $wp_roles;
	$current_user = wp_get_current_user();
	$roles = $current_user->roles;
	$role = array_shift($roles);
	return $wp_roles->role_names[$role];
}


//Create DB schema if not exist
function orto_book_install(){
    global $wpdb;

    $table_adress = $wpdb->prefix.orto_adress;
    $table_email = $wpdb->prefix.orto_email;
    $table_doctor = $wpdb->prefix.orto_doctor;
    $table_slot = $wpdb->prefix.orto_slot;
    $table_reserv = $wpdb->prefix.orto_reserv;

    $adress_query = '
        CREATE TABLE IF NOT EXISTS ' . $table_adress . ' (
        `id_adress` int(10) NOT NULL AUTO_INCREMENT,
        `adress` varchar(64) NOT NULL,

        PRIMARY KEY (`id_adress`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ';

    $wpdb->query($adress_query);

    $adress_query = '
        CREATE TABLE IF NOT EXISTS ' . $table_email . ' (
        `adressID` int(10) NOT NULL,
        `email` varchar(64) NOT NULL,

        FOREIGN KEY (`adressID`) REFERENCES '. $table_adress .' (`id_adress`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ';

    $wpdb->query($adress_query);


    $doctor_query = '
        CREATE TABLE IF NOT EXISTS '.$table_doctor.' (
        `id_doctor` int(10) NOT NULL AUTO_INCREMENT,
        `adressID` int(10) NOT NULL,
        `specialty` varchar(64) NOT NULL,
        `doctor_name` varchar(64),

        PRIMARY KEY (`id_doctor`),
        FOREIGN KEY (`adressID`) REFERENCES '. $table_adress .' (`id_adress`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ';

    $wpdb->query($doctor_query);


    $slot_query = '
        CREATE TABLE IF NOT EXISTS '.$table_slot.' (
        `id_slot` int(10) NOT NULL AUTO_INCREMENT,
        `doctorID` int(10) NOT NULL,
        `date` DATE,
        `time` TIME,
        `available` BOOLEAN DEFAULT TRUE,
        `role` VARCHAR(64),

        PRIMARY KEY (`id_slot`),
        FOREIGN KEY (`doctorID`) REFERENCES '. $table_doctor .' (`id_doctor`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ';

    $wpdb->query($slot_query);
    $wpdb->query('ALTER TABLE '. $table_slot .' ADD INDEX (`date`);');
    $wpdb->query('ALTER TABLE '. $table_slot .' ADD INDEX (`time`);');
    $wpdb->query('ALTER TABLE '. $table_slot .' ADD INDEX (`available`);');
    $wpdb->query('ALTER TABLE '. $table_slot .' ADD INDEX (`role`);');

    $reserv_query = '
        CREATE TABLE IF NOT EXISTS '.$table_reserv.' (
        `id_reserv` int(10) NOT NULL AUTO_INCREMENT,
        `slotID` int(10) NOT NULL,

        `name` VARCHAR(64),
        `adress` VARCHAR(64),
        `email` VARCHAR(64),
        `phone` VARCHAR(64),
        `cancel` BOOLEAN DEFAULT FALSE,

        PRIMARY KEY (`id_reserv`),
        FOREIGN KEY (`slotID`) REFERENCES '. $table_slot .' (`id_slot`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ';

    $wpdb->query($reserv_query);
    $wpdb->query('ALTER TABLE '. $table_reserv .' ADD INDEX (`cancel`);');

    $comment_query = '
        CREATE TABLE IF NOT EXIST ' . $wpdb->prefix.orto_comment .' (
            `id_comment` int(10) NOT NULL AUTO_INCREMENT,
            `date` DATE,
            `comment` VARCHAR(225),
            PRIMARY KEY (`id_comment`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    ';
    $wpdb->query($comment_query);

}

//DELETE DB schema
function orto_book_uninstall(){
        // TODO
}


add_action('wp_footer', 'init_validate', 99 );

register_activation_hook( __FILE__, 'orto_book_install');
register_deactivation_hook( __FILE__, 'orto_book_uninstall');
add_action('admin_menu', 'orto_add_admin');

// add_action('init', 'orto_booking_run');
add_shortcode('booking_ortoped', 'orto_booking_run');?>
