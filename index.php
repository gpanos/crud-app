<?php

$config = array(
    'DB_HOST'     => 'localhost',
    'DB_USER'     => 'root',
    'DB_PASSWORD' => 'root1234root',
    'DB'          => 'testphp',
);

class Persons
{   
    protected $config = null;
    protected $db = null;
    
    public function __construct($config)
    {
        try {

            $this->init( $config )
                 ->initDB();
        } catch (Exception $e) {
            $this->log( $e->getMessage() );

            die( 'Idiots failed to initialize. Aborting...' );
        }
    }
    
    public function init($config)
    {
        $this->config = $config;

        return $this;
    }
    
    public function initDB()
    {
        try {
            if ( $this->db === null ) {

                $this->db = new PDO( "mysql:host={$this->config['DB_HOST']};dbname={$this->config['DB']};charset=utf8",
                                     $this->config['DB_USER'],
                                     $this->config['DB_PASSWORD'] 
                );  

                $this->db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
            }
        } catch ( Exception $e ) {
            $this->log( $e->getMessage() );
            die( 'Idiots failed to connect to the database. Aborting...' );
        }

        return $this;
    }
    
    public function getIdiots()
    {
        $q = <<<BIGSTRING
SELECT 
    *
FROM 
    t_persons
WHERE
    f_person_is_idiot = 1
AND 
    f_person_is_active = 1
BIGSTRING;
        
        return $this->query( $q );
    }
    
    public function create($name)
    {
        $q = <<<BIGSTRING
INSERT INTO 
    t_persons
    (
        f_person_name,
        f_person_is_idiot,
        f_person_is_active
    )
VALUES 
    (
        :name,
        1,
        1
    )
BIGSTRING;

        $params = array(':name' => $name);
        
        return $this->query( $q, $params );
    }
    
    public function update($id, $name)
    {
        $q = <<<BIGSTRING
UPDATE
    t_persons
SET 
    f_person_name = :name,
    f_person_updated_at = :date
WHERE 
    f_person_id = :id        
BIGSTRING;

        $params = array(
            ':name' => $name,
            ':date' => date("Y-m-d"),
            'id'    => $id
        );
        
        return $this->query($q, $params);
    }
    
    public function delete($id)
    {
        $q = <<<BIGSTRING
DELETE FROM 
    t_persons
WHERE 
    f_person_id = :id
BIGSTRING;

        $params = array(':id' => $id);
        
        $this->query($q, $params);
        
    }
    
    public function log( $message )
    {
        error_log( 'Persons error:' );
        error_log( $message );
    }
    
    protected function query( $q, $params = array(), $options = array() )
    {
        try {
            $direction = ( isset( $options['direction'] ) )
                         ? $options['direction']
                         : null;

            $limit = ( isset( $options['limit'] ) )
                     ? intval( $options['limit'] )
                     : null;

            $offset = ( isset( $options['offset'] ) )
                      ? intval( $options['offset'] )
                      : 0;

            $orderBy = ( isset( $options['orderBy'] ) )
                       ? $options['orderBy']
                       : null;

            if ( $orderBy && $direction ) {
                $q .= ' ORDER BY :orderBy :direction';
            }

            if ( $limit ) {
                $q .= ' LIMIT :offset, :limit';
            }

            $stmt = $this->db->prepare( $q );

            foreach( $params as $key => & $value ) {
                $stmt->bindParam( $key, $value );
            }

            if ( $orderBy && $direction ) {
                $stmt->bindParam( ':orderBy', $orderBy );
                $stmt->bindParam( ':direction', $direction );
            }

            if ( $limit ) {
                $stmt->bindParam( ':limit', $limit );
                $stmt->bindParam( ':offset', $offset );
            }

            $q = ltrim( $q );
            $q = strtolower( $q );

            if ( strpos( $q, 'select' ) === 0 ) {
                $stmt->execute();

                return $stmt->fetchAll( PDO::FETCH_ASSOC );
            }
            
            if ( strpos( $q, 'insert') === 0 ) {
                $stmt->execute();
                
                return $this->db->lastInsertId();
            }

            return $stmt->execute();
        } catch( Exception $e ) {
            $this->log( '$this->db->errorCode()' );
            $this->log( $this->db->errorCode() );

            $this->log( $e->getMessage() );
            $this->log( $e->getTraceAsString() );
        }

    }
}

$persons = new Persons($config);

$idiots = $persons->getIdiots();

if (isset($_POST['action']) && !empty($_POST['action'])) {
    $action = $_POST['action'];
    switch($action) {
        case 'update': 
            $name = (string) $_POST['name'];
            $id   = (integer) $_POST['id'];
            $persons->update($id, $name);
            break;
        case 'delete': 
            $id   = (integer) $_POST['id'];
            $persons->delete($id);
            break;
        case 'create':
            $name = (string) $_POST['name'];
            $persons->create($name);
            break;
    }
}

?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>Just Stupid</title>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/css/bootstrap.min.css" integrity="sha384-rwoIResjU2yc3z8GV/NPeZWAv56rSmLldC3R/AZzGRnGxQQKnKkoFVhFQhNUwEyJ" crossorigin="anonymous">

        <style media="screen">
            .add {
                margin-bottom: 20px;
            }
            .header {
                text-align: center;
                text-transform: uppercase;
                margin-top: 100px;
                margin-bottom: 100px;
            }
            .hidden {
                display: none;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="header">
                        <h1>Persons</h1>
                    </div>
                    <button class="btn btn-success add" data-toggle="modal" data-target="#addIdiotModal">Add Idiot</button>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>NAME</th>
                                <th>IDIOT</th>
                                <th>ACTIVE</th>
                                <th style="width:80px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($idiots as $idiot) : ?>
                                <tr id="idiot-<?php echo $idiot['f_person_id']?>">
                                    <td><?php echo $idiot['f_person_id']?></td>
                                    <td>
                                        <span class="name"><?php echo $idiot['f_person_name']?></span>
                                        <input type="text" 
                                               class="form-control edit-name"
                                        >
                                    </td>
                                    <td><span class="details"><?php echo $idiot['f_person_is_idiot']?></span></td>
                                    <td><span class="details"><?php echo $idiot['f_person_is_active']?></span></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-primary edit">
                                                <i class="fa fa-pencil" aria-hidden="true"></i>
                                            </button>
                                            <button type="button" class="btn btn-danger delete">
                                                <i class="fa fa-trash-o" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </td>       
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Modal -->
            <div class="modal fade" id="addIdiotModal" tabindex="-1" role="dialog" aria-labelledby="addIdiotModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addIdiotModalLabel">Add Idiot</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form>
                                <div class="form-group">
                                    <label for="name">Name</label>
                                    <input type="text" class="form-control" id="name">
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-success add-idiot">Save</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Pagination -->
            <nav aria-label="Page navigation example">
                <ul class="pagination">
                    <li class="page-item"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                </ul>
            </nav>
        </div>
        
        <script src="https://code.jquery.com/jquery-3.1.1.min.js" integrity="sha256-hVVnYaiADRTO2PzUGmuLJr8BLUSjGIZsDYGmIJLv2b8=" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/tether/1.4.0/js/tether.min.js" integrity="sha384-DztdAPBWPRXSA/3eYEEUWrWCy7G5KFbe8fFjk5JAIxUYHKkDx6Qin1DkWx51bBrb" crossorigin="anonymous"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/js/bootstrap.min.js" integrity="sha384-vBWWzlZJ8ea9aCX4pEW3rVHjgjt7zpkNpZk+02D9phzyeVkE+jo0ieGizqPLForn" crossorigin="anonymous"></script>
        <script src="https://use.fontawesome.com/e8ae0e5e5a.js"></script>
        
        <script>
            $( document ).ready(function(){
                $('.edit-name').addClass('hidden');
                $('.name').removeClass('hidden');
                
                $('.add-idiot').on('click', function(){
                    var value = $('#name').val();
                    if ( value ) {
                        jQuery.ajax({
                            type: 'POST',
                            data: {'action':'create', 'name': value},
                            success: function(response) {
                                location.reload();
                            }
                        });
                    } else {
                        return false;
                    }
                    
                });
                
                $('.edit').on('click', function(e){

                    var $tr = $(this).closest('tr'),
                        $td = $tr.find('td'),
                        $name = $td.find('.name'),
                        $edit = $td.find('.edit-name');
                    
                    $edit.toggleClass('hidden');
                    $name.toggleClass('hidden');
                    
                    if ( ! $edit.hasClass('hidden')) {
                        $edit.val($name.text());
                    } else {
                        var name = $edit.val();
                        var id = $tr.attr('id');
                        var index = id.lastIndexOf('-') + 1;
                        id = id.slice(index);
                        
                        jQuery.ajax({
                            type: 'POST',
                            data: {'action':'update', 'id': id, 'name': name},
                            success: function() {
                                $name.html(name);
                            }
                        });
                    }
                });
                
                $('.delete').on('click', function(e){
                    
                    var $tr = $(this).closest('tr');
                    
                    var id = $tr.attr('id');
                    var index = id.lastIndexOf('-') + 1;
                    id = id.slice(index);
                    
                    jQuery.ajax({
                        type: 'POST',
                        data: {'action':'delete', 'id': id},
                        success: function() {
                            $('#idiot-' + id).remove();
                        }
                    })
                });
                
                function generatePages(personsCount){
                    
                }
            });
        </script>
    </body>
</html>



