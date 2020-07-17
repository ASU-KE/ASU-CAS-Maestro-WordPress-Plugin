<?php namespace Asu_Research\AsuPublicDirectoryService;

/**
 *  AsuDirectory
 *
 *  This is a static class for interacting with the ASU iSearch service.
 *  To display an ASU iSearch profile, you need their EID (Employee ID?):
 *  https://isearch.asu.edu/profile/{EID}
 *
 *  Profile data can be retrieved as XML and JSON:
 *  to find out about a person given an asurite:
 *  https://asudir-solr.asu.edu/asudir/directory/select?q=asuriteId:{ASURITE}&wt=json
 *  https://asudir-solr.asu.edu/asudir/directory/select?q=asuriteId:{ASURITE}&wt=xml
 *
 *  Thus, in order to access a user's iSearch profile page when you have their ASURITE,
 *  such as provided by the ASU CAS service, their EID must be retrieved using the XML or JSON service.
 *
 *  @author Nathan D. Rollins
 */
class AsuDirectory {

  /**
   * Get user's iSearch record using their ASURITE id
   *
   * @param String $asurite
   * @return Array
   */
  static public function getDirectoryInfoByAsurite($asurite) {
    if ( $asurite == NULL || strlen( $asurite ) < 3 || strlen( $asurite ) > 12 ) {
      return NULL;
    }
    $asurite = urlencode( $asurite );
    $json = file_get_contents( "https://asudir-solr.asu.edu/asudir/directory/select?q=asuriteId:" . $asurite . "&wt=json" );
    if ( empty( $json ) ) {
      return NULL;
    }
    $info = json_decode ( $json, true );
    if ( 0 == $info['response']['numFound'] ) {
      return NULL;
    }
    return $info;
  }

  /**
   * Get user's EID from iSearch array
   *
   * @param  Array   $info
   * @return Integer
   */
  static public function getEid( $info ) {
    if ( isset( $info['response']['docs'][0]['eid'] ) ) {
      return intval( $info['response']['docs'][0]['eid'] );
    }
    return "";
  }

  /**
   * Get user's ASURITE from iSearch array
   *
   * A bit redundant, since we currently only retrieve the iSearch info using the ASURITE as key,
   * but alternate key options may be useful later, making this method useful.
   *
   * @param  Array   $info
   * @return String
   */
  static public function getAsurite( $info ) {
    if ( isset( $info['response']['docs'][0]['asuriteId'] ) ) {
      return strval( $info['response']['docs'][0]['asuriteId'] );
    }
    return "";
  }

  /**
   * Get user's full, display name from iSearch array
   *
   * @param  Array $info
   * @return String
   */
  static public function getDisplayName($info) {
    if ( isset( $info['response']['docs'][0]['displayName'] ) ) {
      return strval( $info['response']['docs'][0]['displayName'] );
    }
    return "";
  }

  /**
   * Get user's last name from iSearch array
   *
   * @param  Array   $info
   * @return String
   */
  static public function getLastName($info) {
    if ( isset( $info['response']['docs'][0]['lastName'] ) ) {
      return strval( $info['response']['docs'][0]['lastName'] );
    }
    return "";
  }

  /**
   * Get user's first name from iSearch array
   *
   * @param  Array   $info
   * @return String
   */
  static public function getFirstName($info) {
    if ( isset( $info['response']['docs'][0]['firstName'] ) ) {
      return strval( $info['response']['docs'][0]['firstName'] );
    }
    return "";
  }

  /**
   * Get user's email address from iSearch array
   *
   * @param  Array   $info
   * @return String
   */
  static public function getEmail($info) {
    if ( isset( $info['response']['docs'][0]['emailAddress'] ) ) {
      return strval( $info['response']['docs'][0]['emailAddress'] );
    }
    return "";
  }

  /**
   * Return T/F whether a user is listed as a student in iSearch
   *
   * @param  Array   $info
   * @return String
   */
  static public function isStudent( $info ) {
    if ( isset( $info['response']['docs'][0]['affiliations'] ) ) {
      foreach ( $info['response']['docs'][0]['affiliations'] as $affiliation ) {
        if ( 'Student' == $affiliation ) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * Return T/F whether a user is listed as faculty in iSearch
   *
   * @param  Array   $info
   * @return String
   */
  static public function isFaculty( $info ) {
    if ( isset( $info['response']['docs'][0]['affiliations'] ) ) {
      foreach ( $info['response']['docs'][0]['affiliations'] as $affiliation ) {
        if ( 'Employee' == $affiliation ) {
          foreach ( $info['response']['docs'][0]['emplClasses'] as $employee_class ) {
            if ( 'Faculty' == $employee_class ) {
              return TRUE;
            }
          }
        }
      }
    }
    return FALSE;
  }

  /**
   * Return T/F whether a user is listed as staff in iSearch
   *
   * Specifically, this function searches for the 'Employee' affiliation AND 'University Staff' employee classification.
   * Student workers and Graduate Assistants have the employee affiliation in addition to their Student affiliation,
   * but they have a different employee classification.
   *
   * @param  Array   $info
   * @return String
   */
  static public function isStaff( $info ) {
    if ( isset( $info['response']['docs'][0]['affiliations'] ) ) {
      foreach ( $info['response']['docs'][0]['affiliations'] as $affiliation ) {
        if ( 'Employee' == $affiliation ) {
          foreach ( $info['response']['docs'][0]['emplClasses'] as $employee_class ) {
            if ( 'University Staff' == $employee_class ) {
              return TRUE;
            }
          }
        }
      }
    }
    return FALSE;
  }

  /**
   * Get user's ASU primary affiliation/status (student, faculty, staff) from iSearch array
   *
   * This is a somewhat personalised function written for Sustainability Connect's (sustainabilityconnect.asu.edu) needs.
   * Just in case a university staff user might also be classified as a student if they are enrolled for students,
   * this function prioritises employee classification: Student > Faculty > Staff.
   *
   * @param  Array   $info
   * @return String
   */
  static public function getUserType( $info ) {
    $student = FALSE;
    $faculty = FALSE;
    $staff = FALSE;
    if ( isset( $info['response']['docs'][0]['affiliations'] ) ) {
      foreach ( $info['response']['docs'][0]['affiliations'] as $affiliation ) {
        if ( 'Student' == $affiliation ) {
          $student = TRUE;
        }
        if ( 'Employee' == $affiliation ) {
          foreach ( $info['response']['docs'][0]['emplClasses'] as $employee_class ) {
            if ( 'Faculty' == $employee_class ) {
              $faculty = TRUE;
            } elseif ( 'University Staff' == $employee_class ) {
              $staff = TRUE;
            }
          }
        }
      }
    }
    // in case, user has multiple classifications (staff enrolled as student)
    // role precedence: student > faculty > staff
    if ( $student ) {
      return 'student';
    } elseif ( $faculty ) {
      return 'faculty';
    } elseif ( $staff ) {
      return 'staff';
    } else {
      return FALSE;
    }
  }

  /**
   * Return T/F whether a user is listed in iSearch as majoring in a degree program from the School of Sustainability
   *
   * @param  Array   $info
   * @return String
   */
  static public function hasSosPlan( $info ) {
    if ( $info['response']['numFound'] > 0 ) {
      if ( !empty( $info['response']['docs'][0]['programs'] ) ) {
        foreach ( $info['response']['docs'][0]['programs'] as $program ) {
          // look for SOS program
          if ( 'School of Sustainability' == $program ) {
            foreach ( $info['response']['docs'][0]['majors'] as $major ) {
              // is student majoring in Sustainability
              if ( 'Sustainability' == $major ) {
                return TRUE;
              }
            }
          }
        }
      }
    }

    return FALSE;
  }
}
