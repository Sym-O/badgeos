<?php

// check if given achievement is in aimed achievements list
function check_achievement( $user_id, $achievement_id ) {

    $achievements = badgeos_get_user_achievements( array( 'user_id' => $user_id ) );

    foreach ( $achievements as $achievement )
        if ( $achievement->ID == $achievement_id )
            return false;

    return true;
}

// update given user aimed_badges depending on achievement_id
function update_aimed_achievements( $user_id, $achievement_id ){

    $aimed = get_user_meta( $user_id, 'aimed_badges', true );

    if ( $aimed === "" )
        $aimed_array = array();
    else
        $aimed_array = array_map('intval', explode(" ", $aimed ) );

    $key = array_search( $achievement_id, $aimed_array );
    // if the value already exist -> delete it
    if ( $key !== false )
        unset($aimed_array[$key]);
    // otherwise add it
    else
        array_push( $aimed_array, $achievement_id );

    if ( ! count($aimed_array) > 0 )
        $aimed =  "";
    else
        $aimed = join( " ", $aimed_array );

    return $aimed;

}


// template to generate a my_badges page
function my_badges() {

    global $user_ID;

    echo "<h3>My aimed badges</h3>";

    if ( get_the_author_meta('aimed_badges', $user_ID) )
        echo get_the_author_meta('aimed_badges', $user_ID);
    else
        echo "None";
}

// used in admin/user-profile
function show_aimed_badges ( $user ) {

    $aimed = esc_attr( get_user_meta($user->ID, 'aimed_badges', true));

    echo '
        <h3>Aimed badges</h3>
        <div>
            <input type="text" name="aimed_badges" id="aimed_badges" value="'.$aimed.'" class="regular-text" /><br />
            <span class="description"> Badges you intent to obatain </span>
        </div>';

    my_badges();
}


// save badge from admin user modification
function save_aimed_badges( $user_id ) {

    if ( ! current_user_can( 'edit_user', $user_id ) )
        return false;
    // explode aimed and chek badge one by one

    update_usermeta( $user_id, 'aimed_badges', $_POST['aimed_badges'] );
}

// delete given achievement id from aimed achievments list if present
function update_aimed_achievement_on_award( $user_id, $achievement_id ) {

    if ( $user_id > 0 ) {

            $aimed = update_aimed_achievements( $user_id, $achievement_id );
            update_usermeta( $user_id, 'aimed_badges', $aimed );
        }
    }

?>
