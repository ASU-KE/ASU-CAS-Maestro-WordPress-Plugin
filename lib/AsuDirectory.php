<?php namespace Asu_Research\AsuPublicDirectoryService;

/**
 *  AsuDirectory
 *
 *  This is a static class for interacting with the ASU Search service.
 *
 *  Profile data can be retrieved as JSON:
 *  https://search.asu.edu/api/v1/webdir-profiles/faculty-staff/filtered?asurite_ids={ASURITE_ID}
 *
 *  April-2023: Replacing references to asudir-solr endpoint with current end point from search.asu.edu
 *
 *  @author Nathan D. Rollins
 *  @author Steven Ryan
 */
class AsuDirectory {

  /**
   * Get user's iSearch record using their ASURITE id
   *
   * @param String $asurite
   * @return Array
   */
  static public function getDirectoryInfoByAsurite($asurite) {

    // Sanity check on the ASURITE ID to be searched.
    if ( $asurite == NULL || strlen( $asurite ) < 3 || strlen( $asurite ) > 12 ) {
      return NULL;
    }
    $asurite = urlencode( $asurite );

    $search_json = 'https://search.asu.edu/api/v1/webdir-profiles/faculty-staff/filtered?asurite_ids=' . $asurite . '&size=1&client=asu_wp_cas_plugin';

    $search_request = wp_safe_remote_get( $search_json );

    // Error check for invalid JSON.
    if ( is_wp_error( $search_request ) ) {
      return NULL; // Bail early.
    }

    $search_body   = wp_remote_retrieve_body( $search_request );
    $search_data   = json_decode( $search_body );

    if ( ! empty( $search_data ) ) {

      // Returned JSON results indicate total_results = 0 if record not found.
      if ( 0 == $search_data->meta->page->total_results ) {
        return NULL;
      }

      // We're safe. Return the results portion of the record.
      return $search_data->results[0];
    }
  }

  /**
   * Get user's EID from Search array
   *
   * @param  Array   $info
   * @return Integer
   */
  static public function getEid( $info ) {
    if ( isset( $info->eid->raw ) ) {
      return intval( isset( $info->eid->raw ) );
    }
    return "";
  }

  /**
   * Get user's ASURITE from Search array
   *
   * A bit redundant, since we currently retrieve the Search info using the ASURITE as key,
   * but alternate key options may be useful later, making this method useful.
   *
   * @param  Array   $info
   * @return String
   */
  static public function getAsurite( $info ) {
    if ( isset( $info->asurite_id->raw ) ) {
      return strval( $info->asurite_id->raw );
    }
    return "";
  }

  /**
   * Get user's full, display name from Search array
   *
   * @param  Array $info
   * @return String
   */
  static public function getDisplayName($info) {
    if ( isset( $info->display_name->raw ) ) {
      return strval( $info->display_name->raw );
    }
    return "";
  }

  /**
   * Get user's last name from Search array
   * April 2023 - Give preference to "preferred last name" field if present.
   *
   * @param  Array   $info
   * @return String
   */
  static public function getLastName($info) {

    $lastname = '';

    if ( isset( $info->last_name->raw ) ) {
      $lastname = strval( $info->last_name->raw );
    }

    if ( isset( $info->preferred_last_name->raw ) ) {
      $lastname = strval( $info->preferred_last_name->raw );
    }

    return $lastname;
  }

  /**
   * Get user's first name from Search array
   * April 2023 - Give preference to "preferred first name" field if present.
   *
   * @param  Array   $info
   * @return String
   */
  static public function getFirstName($info) {

    $firstname = '';

    if ( isset( $info->first_name->raw ) ) {
      $firstname = strval( $info->first_name->raw );
    }

    if ( isset( $info->preferred_first_name->raw ) ) {
      $firstname = strval( $info->preferred_first_name->raw );
    }

    return $firstname;
  }

  /**
   * Get user's email address from Search array
   *
   * @param  Array   $info
   * @return String
   */
  static public function getEmail($info) {
    if ( isset( $info->email_address->raw ) ) {
      return strval( $info->email_address->raw );
    }
    return "";
  }

  /**
   * Return T/F whether a user is listed as a student in Search
   *
   * April-2023: Public results from Search no longer include student data.
   * Function will now always return false.
   *
   * @param  Array   $info
   * @return String
   */
  static public function isStudent( $info ) {
    return FALSE;
  }

  /**
   * Return T/F whether a user is listed as faculty in iSearch
   *
   * Faculty rankings are now a numerical attribute in Search.
   * Any number besides 99 is a faculty member.
   *
   * @param  Array   $info
   * @return String
   */
  static public function isFaculty( $info ) {

    if ( isset( $info->faculty_rank->raw[0] ) ) {

      if ( 99 !== $info->faculty_rank->raw[0] ) {
        return TRUE;
      }

      return FALSE;
    }
  }

  /**
   * Return T/F whether a user is listed as staff in iSearch
   *
   * Faculty rankings are now a numerical attribute in Search.
   * A returned value of 99 indicates a staff member.
   *
   * @param  Array   $info
   * @return String
   */
  static public function isStaff( $info ) {
    if ( isset( $info->faculty_rank->raw[0] ) ) {

      if ( 99 == $info->faculty_rank->raw[0] ) {
        return TRUE;
      }

      return FALSE;
    }
  }

  // April-2023: The following two public functions are no longer relevant given the scope of the new Search results.
  // getUserType can be replicated by calles to either isStaff or isFaculty. Students are excluded from the results.
  // hasSOSPlan was looking for a particular degree program assigned to a student record.

  // /**
  //  * Get user's ASU primary affiliation/status (student, faculty, staff) from iSearch array
  //  *
  //  * This is a somewhat personalised function written for Sustainability Connect's (sustainabilityconnect.asu.edu) needs.
  //  * Just in case a university staff user might also be classified as a student if they are enrolled for students,
  //  * this function prioritises employee classification: Student > Faculty > Staff.
  //  *
  //  * @param  Array   $info
  //  * @return String
  //  */
  // static public function getUserType( $info ) {
  //   $student = FALSE;
  //   $faculty = FALSE;
  //   $staff = FALSE;
  //   if ( isset( $info['response']['docs'][0]['affiliations'] ) ) {
  //     foreach ( $info['response']['docs'][0]['affiliations'] as $affiliation ) {
  //       if ( 'Student' == $affiliation ) {
  //         $student = TRUE;
  //       }
  //       if ( 'Employee' == $affiliation ) {
  //         foreach ( $info['response']['docs'][0]['emplClasses'] as $employee_class ) {
  //           if ( 'Faculty' == $employee_class ) {
  //             $faculty = TRUE;
  //           } elseif ( 'University Staff' == $employee_class ) {
  //             $staff = TRUE;
  //           }
  //         }
  //       }
  //     }
  //   }
  //   // in case, user has multiple classifications (staff enrolled as student)
  //   // role precedence: student > faculty > staff
  //   if ( $student ) {
  //     return 'student';
  //   } elseif ( $faculty ) {
  //     return 'faculty';
  //   } elseif ( $staff ) {
  //     return 'staff';
  //   } else {
  //     return FALSE;
  //   }
  // }

  // /**
  //  * Return T/F whether a user is listed in iSearch as majoring in a degree program from the School of Sustainability
  //  *
  //  * @param  Array   $info
  //  * @return String
  //  */
  // static public function hasSosPlan( $info ) {
  //   if ( $info['response']['numFound'] > 0 ) {
  //     if ( !empty( $info['response']['docs'][0]['programs'] ) ) {
  //       foreach ( $info['response']['docs'][0]['programs'] as $program ) {
  //         // look for SOS program
  //         if ( 'School of Sustainability' == $program ) {
  //           foreach ( $info['response']['docs'][0]['majors'] as $major ) {
  //             // is student majoring in Sustainability
  //             if ( 'Sustainability' == $major ) {
  //               return TRUE;
  //             }
  //           }
  //         }
  //       }
  //     }
  //   }

  //   return FALSE;
  // }
}

