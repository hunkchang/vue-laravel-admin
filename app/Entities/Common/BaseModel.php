<?php
/**
 * Created by PhpStorm.
 * User: zdw
 * Date: 2015/1/4
 * Time: 15:17
 */

namespace Models\Common;

class BaseModel extends \Eloquent
{
    public           $timestamps      = false;
    protected        $created_at      = true;
    protected        $updated_at      = true;
    protected        $searchTable;
    protected        $listColumn      = [];
    protected        $orderBy         = [];
    protected        $groupBy         = [];
    protected        $hasPrimaryKey   = true;
    protected        $joinTable       = [];
    protected        $hasPage         = true;
    protected        $orWhereFunction = null;
    public static    $saveRules       = [];
    public static    $imgRules        = [ 'image/jpeg' , 'image/jpg' , 'image/png' ];
    protected static $instance;

    /**
     * @return static mixed
     */
    public static function getInstance()
    {
        if ( isset( self::$instance[ static::class ] ) || empty( self::$instance[ static::class ] ) ) {
            self::$instance[ static::class ] = new static();
        }
        return self::$instance[ static::class ];
    }

    public function __construct( array $attributes = [] )
    {
        parent::__construct ( $attributes );
        $this->__init ();
    }

    /**
     * @param bool $hasPage
     * @return BaseModel
     */
    public function setHasPage( bool $hasPage )
    {
        $this->hasPage = $hasPage;
        return $this;
    }

    /**
     * @return array
     */
    public static function getSaveRules() : array
    {
        return self::$saveRules;
    }

    /**
     * @param array $saveRules
     */
    public static function setSaveRules( array $saveRules ) : void
    {
        self::$saveRules = $saveRules;
    }

    public function __init()
    {

    }

    public function getOneData( $filed = '*' , $where )
    {

        if ( $filed == '*' ) {
            $info = $this->where ( $where )->first ();
            if ( empty( $info ) ) {
                return [];
            }
            return $info->toArray ();
        } else {
            $info = $this->where ( $where )->get ( $filed );
            if ( empty( $info->isEmpty () ) ) {
                return [];
            }
            return $info->toArray ();
        }

    }


    //修改一条数据

    /**
     * @param $id
     * @param $data
     * @return bool|int
     */
    public function updateById( $id , $data )
    {
        $where = [ $this->primaryKey => $id ];
        if ( $this->where ( $where )->update ( $data ) === false ) {
            return false;
        }
        return true;
    }

    /** 删除一条数据
     * @param array $ids
     * @return int
     *
     */
    public function delById( $ids = [] )
    {
        if ( !is_array ( $ids ) ) {
            $ids = [ $ids ];
        }
        return $this->destroy ( $ids );
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getDataByid( $id )
    {
        $where = [ $this->getKeyName () => $id ];
        return $this->getOneData ( '*' , $where );
    }

    //从搜索引擎获取数据

    /**
     * @param string $q 搜索关键词
     * @param array  $where 条件
     * @param array  $where_not 排除条件
     * @param array  $range 区间搜索
     * @param array  $fields 搜索字段
     * @param array  $sort 排序
     * @param array  $facets 层面搜索
     * @param int    $page 页数
     * @param int    $listRows 查询第几条
     * @param array  $aggs
     * @return mixed
     * @internal param string $table 搜索引擎表
     */
    public function getListByEc( $q = '' , $where = [] , $where_not = [] , $range = [] , $fields = [] , $sort = [ '_id' => 'desc' ] , $facets = [] , $page = 1 , $listRows = 20 , $aggs = [] )
    {
        $this->getTable ();
        $ec   = new ElasticSearch( $this->searchTable );
        $data = $this->getEcQuery ( $q , $where , $where_not , $range , $fields , $sort , $facets , $page , $listRows , $aggs );
        return $ec->search ( $data );
    }


    public function getEcQuery( $q = '' , $where = [] , $where_not = [] , $range = [] , $fields = [] , $sort = [ '_id' => 'desc' ] , $facets = [] , $page = 1 , $listRows = 20 , $aggs = [] , $color_where = [] )
    {
        if ( (int) $page < 1 ) {
            $page = 1;
        }
        $data = [];
        $send = [];
//        $form = $page*$listRows;
        $start = ( $page - 1 ) * $listRows;
        $this->getTable ();

        $ec = new ElasticSearch( $this->searchTable );
        $ec->reset ();
        $ec->setLimit ( $start , $listRows );


        if ( !empty( $q ) ) {
            if ( !is_array ( $q ) ) {
                //每个条件支持多字段搜搜索
                $keywords[ 'query' ]    = $q;
                $keywords[ 'fields' ]   = [ 'keywords' ];
                $keywords[ 'operator' ] = 'and';
                //支持多条件字段搜索
                $keywordsWhere[] = $keywords;
                //keywords
            } else {
                $keywordsWhere = $q;
            }
            $ec->keyWordsWhere ( $keywordsWhere );
        }

        if ( !empty( $where ) ) {
            $ec->where ( $where );
        }

        if ( !empty( $where_not ) ) {
            $ec->where_not ( $where_not );
        }

        if ( !empty( $range ) ) {
            $ec->range ( $range );
        }

        if ( !empty( $color_where ) ) {
            $ec->setColor ( $color_where );
        }

        if ( !empty( $this->color_map ) ) {
            $ec->setColorMap ( $this->color_map );
        }
        if ( isset( $facets[ 'facets' ] ) && !empty( $facets[ 'facets' ] ) ) {
            $facetUnset = [];
            if ( isset( $facets[ 'unset' ] ) && !empty( $facets[ 'unset' ] ) ) {
                $facetUnset = $facets[ 'unset' ];
            }
            if ( isset( $facets[ 'global' ] ) && !empty( $facets[ 'global' ] ) ) {
                $ec->facets ( $facets[ 'facets' ] , true , $facetUnset );
            } else {
                $ec->facets ( $facets[ 'facets' ] );
            }
        }


        if ( !empty( $fields ) ) {
            $ec->fields ( $fields );
        }

        //聚合
        if ( !empty( $aggs ) ) {
            $ec->setAggs ( $aggs );
        }

        if ( !empty( $this->ec_exists ) ) {
            $ec->exists ( $this->ec_exists );
        }

        $ec->sort ( $sort );
        return $ec->getQuery ();
    }

    protected function __initGetList()
    {

    }

    /**
     * 获取数据列表
     * @param array $where
     * @param array $orWhere
     * @param array $whereIn
     * @param int   $pageCount
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator | BaseModel
     * @internal param int $pagCount
     */
    public function getList( $where = [] , $orWhere = [] , $whereIn = [] , $pageCount = 18 )
    {
        $this->__initGetList ();
        $columns = '*';
        if ( !empty( $this->listColumn ) ) {
            $columns = $this->listColumn;
            if ( $this->hasPrimaryKey ) {
                array_unshift ( $columns , $this->primaryKey );
            }
        }
        $orderBy = [];
        if ( $this->hasPrimaryKey ) {
            $orderBy = [ $this->primaryKey => 'desc' ];
        }

        if ( !empty( $this->orderBy ) ) {
            $orderBy = $this->orderBy;
        }

        $list = $this->where ( $where );
        if ( !empty( $orWhere ) ) {
            $list = $list->orWhere ( $orWhere );
        }

        if ( !empty( $whereIn ) ) {
            foreach ( $whereIn as $key => $value ) {
                $list = $list->whereIn ( $key , $value );
            }
        }

        if ( !empty( $orderBy ) ) {
            foreach ( $orderBy as $key => $by ) {
                $list = $list->orderBy ( $key , $by );
            }
        }

        if ( !is_null ( $this->joinTable ) ) {
            foreach ( $this->joinTable as $key => $join ) {
                $table = $key;
                if ( $join[ 'type' ] == 'left' ) {
                    $list = $list->leftJoin ( $table , $join[ 'field' ][ 'first' ] , $join[ 'field' ][ 'operator' ] , $join[ 'field' ][ 'second' ] );
                }
            }
        }
        //group by
        if ( !empty( $this->groupBy ) ) {
            foreach ( $this->groupBy as $field ) {
                $list = $list->groupBy ( $field );
            }
        }

        if ( $this->hasPage ) {
            $list = $list->select ( $columns )->paginate ( $pageCount );
        } else {
            $list = $list->select ( $columns )->get ();
        }
        $this->__completeGetList ( $list );
        return $list;
    }

    //格式化列表数据
    protected function __completeGetList( &$list )
    {

    }

    public function findById( $id = 0 )
    {
        return self::find ( $id );
    }

    /**
     * @param array $listColumn
     */
    public function setListColumn( array $listColumn ) : void
    {
        $this->listColumn = $listColumn;
    }

    /**
     * @param array $orderBy
     */
    public function setOrderBy( array $orderBy ) : void
    {
        $this->orderBy = $orderBy;
    }

    /**
     *
     * @param array  $where
     * @param array  $orWhere
     * @param array  $whereIn
     * @param array  $orderBy
     * @param string $colnums
     * @param int    $offset
     * @param int    $pageSize
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|BaseModel[]
     */
    public function getListNoPage( $where = [] , $orWhere = [] , $whereIn = [] , $offset = 0 , $pageSize = 20 )
    {
        $list = self::where ( $where );

        if ( !empty( $orWhere ) ) {
            $list = $list->orWhere ( $orWhere );
        }

        if ( !empty( $whereIn ) ) {
            $list = $list->whereIn ( $whereIn );
        }

        if ( !empty( $orWhere ) ) {
            $list = $list->orWhere ( $orWhere );
        }

        if (!empty($this->orWhereFunction)){
            $list = $list->where ($this->orWhereFunction);
        }

        if ( !empty( $this->orderBy ) ) {
            foreach ( $this->orderBy as $key => $value ) {
                $list = $list->orderBy ( $key , $value );
            }
        }
        if ( $this->hasPage ) {
            return $list->offset ( $offset )->select ( $this->listColumn )->limit ( $pageSize )->get ();
        }

        return $list->select ($this->listColumn)->get ();
    }

}