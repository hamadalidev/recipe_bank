<template>
  <div class="app-container">
    <div class="filter-container">
      <el-input
        v-model="listQuery.search"
        placeholder="Search recipes..."
        style="width: 150px;"
        class="filter-item"
        size="small"
        @keyup.enter.native="handleFilter"
      />
      <el-select
        v-model="listQuery.cuisine_type_id"
        placeholder="Cuisine"
        clearable
        style="width: 120px"
        class="filter-item"
        size="small"
      >
        <el-option
          v-for="item in cuisineTypes"
          :key="item.id"
          :label="item.name"
          :value="item.id"
        />
      </el-select>
      <el-button type="primary" icon="el-icon-search" size="small" @click="handleFilter">
        Search
      </el-button>
      <el-button  icon="el-icon-refresh-left" size="small" @click="clearFilters">
        Clear
      </el-button>
      <el-button
        v-if="canCreateRecipe"
        class="filter-item"
        style="margin-left: 8px;"
        type="primary"
        icon="el-icon-plus"
        size="small"
        @click="handleCreate"
      >
        Add Recipe
      </el-button>
    </div>

    <div class="table-container">
      <el-table
        :key="tableKey"
        v-loading="listLoading"
        :data="list"
        border
        highlight-current-row
        size="mini"
        :resizable="false"
        @sort-change="sortChange"
      >
      <el-table-column label="ID" prop="id" sortable="custom" align="center" width="100" :resizable="false">
        <template slot-scope="{row}">
          <span>{{ row.id }}</span>
        </template>
      </el-table-column>
      
      <el-table-column label="Recipe Name"  :resizable="false">
        <template slot-scope="{row}">
          <router-link :to="'/recipes/view/'+row.id" class="link-type">
            <span>{{ row.name }}</span>
          </router-link>
        </template>
      </el-table-column>

      <el-table-column label="Type" align="center" :resizable="false">
        <template slot-scope="{row}">
          <el-tag type="primary" size="mini">{{ row.cuisine_type.name }}</el-tag>
        </template>
      </el-table-column>

      <el-table-column label="Actions" align="center" width="160" class-name="small-padding fixed-width" :resizable="false">
        <template slot-scope="{row}">
          <el-button type="primary" size="mini" icon="el-icon-view" @click="handleView(row)" title="View" plain>
          </el-button>
          <el-button
            v-if="canEditRecipe"
            size="mini"
            type="primary"
            icon="el-icon-edit"
            @click="handleUpdate(row)"
            title="Edit"
            plain
          >
          </el-button>
          <el-button
            v-if="canDeleteRecipe"
            size="mini"
            type="danger"
            icon="el-icon-delete"
            @click="handleDelete(row)"
            title="Delete"
            plain
          >
          </el-button>
        </template>
      </el-table-column>
      </el-table>
    </div>

    <pagination
      v-show="total>0"
      :total="total"
      :page.sync="listQuery.page"
      :limit.sync="listQuery.limit"
      @pagination="getList"
    />

    <!-- Recipe Form Dialog -->
    <el-dialog :title="textMap[dialogStatus]" :visible.sync="dialogFormVisible" width="75%" :close-on-click-modal="false">
      <recipe-form
        ref="recipeForm"
        :recipe-data="temp"
        :cuisine-types="cuisineTypes"
        :dialog-status="dialogStatus"
        @submit="handleSubmit"
        @cancel="dialogFormVisible = false"
      />
    </el-dialog>

    <!-- Recipe View Dialog -->
    <el-dialog title="Recipe Details" :visible.sync="viewDialogVisible" width="65%" class="recipe-view-dialog">
      <div v-if="selectedRecipe" class="recipe-details">
        <el-row :gutter="20">
          <el-col :span="12">
            <div class="detail-item">
              <label>Name:</label>
              <span>{{ selectedRecipe.name }}</span>
            </div>
          </el-col>
          <el-col :span="12">
            <div class="detail-item">
              <label>Cuisine Type:</label>
              <el-tag size="mini" type="primary">{{ selectedRecipe.cuisine_type.name }}</el-tag>
            </div>
          </el-col>
        </el-row>


        <div v-if="selectedRecipe.attachments && selectedRecipe.attachments.length > 0" class="detail-item">
          <label>Image:</label>
          <div class="recipe-image-container">
            <img 
              v-for="attachment in selectedRecipe.attachments.filter(a => a.is_image)" 
              :key="attachment.id"
              :src="attachment.url" 
              :alt="selectedRecipe.name"
              class="recipe-detail-image"
              @error="handleImageError"
            />
          </div>
        </div>

        <div class="detail-item">
          <label>Description:</label>
          <p>{{ selectedRecipe.description }}</p>
        </div>
        
        <div class="detail-item">
          <label>Ingredients:</label>
          <div class="ingredients-list">
            <el-tag
              v-for="(ingredient, index) in selectedRecipe.ingredients"
              :key="index"
              size="small"
              class="ingredient-tag"
            >
              {{ ingredient }}
            </el-tag>
          </div>
        </div>
        
        <div class="detail-item">
          <label>Steps:</label>
          <ol class="steps-list">
            <li v-for="(step, index) in selectedRecipe.steps" :key="index">
              {{ step }}
            </li>
          </ol>
        </div>
        
        <div class="detail-item">
          <label>Created by:</label>
          <span>{{ selectedRecipe.user.name }} ({{ selectedRecipe.user.email }})</span>
        </div>
        
        <div class="detail-item">
          <label>Created:</label>
          <span>{{ selectedRecipe.created_at | parseTime('{y}-{m}-{d} {h}:{i}') }}</span>
        </div>
      </div>
      
      <div slot="footer" class="dialog-footer">
        <el-button @click="viewDialogVisible = false">Close</el-button>
        <el-button
          v-if="canEditRecipe"
          type="primary"
          @click="editFromView"
        >
          Edit Recipe
        </el-button>
      </div>
    </el-dialog>
  </div>
</template>

<script>
import { getRecipes, deleteRecipe, getCuisineTypes } from '@/api/recipe'
// import waves from '@/directive/waves' // waves directive
import { parseTime } from '@/utils'
import Pagination from '@/components/Pagination' // secondary package based on el-pagination
import RecipeForm from './components/RecipeForm'
import { mapGetters } from 'vuex'

export default {
  name: 'RecipeTable',
  components: { Pagination, RecipeForm },
  // directives: { waves },
  filters: {
    truncate(value, length) {
      if (!value) return ''
      if (value.length <= length) return value
      return value.substring(0, length) + '...'
    },
    parseTime(time, cFormat) {
      return parseTime(time, cFormat)
    }
  },
  data() {
    return {
      tableKey: 0,
      list: null,
      total: 0,
      listLoading: true,
      listQuery: {
        page: 1,
        limit: 10,
        search: undefined,
        cuisine_type_id: undefined,
        sort: 'created_at',
        order: 'desc'
      },
      cuisineTypes: [],
      temp: {
        id: undefined,
        name: '',
        description: '',
        ingredients: [],
        steps: [],
        cuisine_type_id: undefined,
        can_edit: true
      },
      dialogFormVisible: false,
      viewDialogVisible: false,
      selectedRecipe: null,
      dialogStatus: '',
      textMap: {
        update: 'Edit Recipe',
        create: 'Create Recipe'
      },
      downloadLoading: false
    }
  },
  computed: {
    ...mapGetters(['permissions', 'roles']),
    canCreateRecipe() {
      return this.permissions.includes('add-recipe')
    },
    canEditRecipe() {
      return this.permissions.includes('edit-recipe')
    },
    canDeleteRecipe() {
      return this.permissions.includes('delete-recipe')
    }
  },
  created() {
    this.getList()
    this.getCuisineTypesList()
  },
  methods: {
    getList() {
      this.listLoading = true
      const params = {
        length: this.listQuery.limit,
        page: this.listQuery.page,
        search: this.listQuery.search,
        cuisine_type_id: this.listQuery.cuisine_type_id,
        column: this.listQuery.sort,
        dir: this.listQuery.order
      }

      // Remove undefined values
      Object.keys(params).forEach(key => {
        if (params[key] === undefined || params[key] === '') {
          delete params[key]
        }
      })

      getRecipes(params).then(response => {
        this.list = response.data.list
        this.total = response.data.pagination.total
        this.listLoading = false
      }).catch(() => {
        this.listLoading = false
      })
    },
    getCuisineTypesList() {
      getCuisineTypes().then(response => {
        this.cuisineTypes = response.data
      })
    },
    handleFilter() {
      this.listQuery.page = 1
      this.getList()
    },
    clearFilters() {
      this.listQuery.search = undefined
      this.listQuery.cuisine_type_id = undefined
      this.listQuery.page = 1
      this.getList()
    },
    sortChange(data) {
      const { prop, order } = data
      if (prop === 'id') {
        this.sortByID(order)
      } else {
        this.listQuery.sort = prop
        this.listQuery.order = order === 'ascending' ? 'asc' : 'desc'
        this.handleFilter()
      }
    },
    sortByID(order) {
      this.listQuery.sort = 'id'
      this.listQuery.order = order === 'ascending' ? 'asc' : 'desc'
      this.handleFilter()
    },
    resetTemp() {
      this.temp = {
        id: undefined,
        name: '',
        description: '',
        ingredients: [],
        steps: [],
        cuisine_type_id: undefined,
        can_edit: true
      }
    },
    handleView(row) {
      this.selectedRecipe = Object.assign({}, row)
      this.viewDialogVisible = true
    },
    editFromView() {
      this.viewDialogVisible = false
      this.handleUpdate(this.selectedRecipe)
    },
    handleCreate() {
      this.resetTemp()
      this.dialogStatus = 'create'
      this.dialogFormVisible = true
      this.$nextTick(() => {
        this.$refs.recipeForm.clearValidate()
        this.$refs.recipeForm.clearImage()
      })
    },
    handleUpdate(row) {
      this.temp = Object.assign({}, row) // copy obj
      // Ensure cuisine_type_id is set correctly for editing
      if (row.cuisine_type && row.cuisine_type.id) {
        this.temp.cuisine_type_id = row.cuisine_type.id
      }
      this.dialogStatus = 'update'
      this.dialogFormVisible = true
      this.$nextTick(() => {
        this.$refs.recipeForm.clearValidate()
      })
    },
    handleSubmit() {
      this.dialogFormVisible = false
      this.getList()
    },
    handleDelete(row) {
      this.$confirm('This will permanently delete the recipe. Continue?', 'Warning', {
        confirmButtonText: 'OK',
        cancelButtonText: 'Cancel',
        type: 'warning'
      }).then(() => {
        deleteRecipe(row.id).then(() => {
          this.getList()
          this.$message({
            type: 'success',
            message: 'Recipe deleted successfully!'
          })
        })
      }).catch(() => {
        this.$message({
          type: 'info',
          message: 'Delete cancelled'
        })
      })
    },
    handleImageError(event) {
      console.error('Image failed to load:', event.target.src)
      event.target.style.display = 'none'
    }
  }
}
</script>

<style scoped>
.app-container {
  padding: 20px;
  background-color: #f8fafc;
  min-height: 100vh;
}

.filter-container {
  padding: 20px;
  margin-bottom: 20px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
}

/* Filter items spacing handled by gap in parent */

.table-container {
  width: 100%;
  overflow-x: auto;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.text-muted {
  color: #909399;
  font-size: 10px;
}

.link-type {
  color: #409EFF;
  text-decoration: none;
  font-weight: 500;
  font-size: 12px;
}

.link-type:hover {
  color: #66b1ff;
}

/* Improved table styling */
.el-table {
  font-size: 13px;
  table-layout: fixed !important;
}

.el-table .el-table__header-wrapper th {
  resize: none !important;
}

.el-table .el-table__body-wrapper td {
  resize: none !important;
}

.el-table .cell {
  padding: 2px 4px;
  line-height: 1.2;
  word-break: break-word;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.el-table td, .el-table th.is-leaf {
  border-bottom: 1px solid #ebeef5;
  padding: 8px 12px;
  height: auto;
}

.el-table th {
  background-color: #f8f9fa;
  font-weight: 600;
  font-size: 12px;
  padding: 12px;
  height: auto;
}

.el-table th .cell {
  padding: 2px 4px;
}

.el-table--mini td {
  padding: 8px 12px;
  height: auto;
}

.el-table--mini th {
  padding: 12px;
  height: auto;
}

/* Enhanced filter inputs */
.el-input--small .el-input__inner {
  height: 36px;
  line-height: 36px;
  font-size: 13px;
  padding: 0 12px;
  border-radius: 6px;
}

.el-select--small .el-input__inner {
  height: 36px;
  line-height: 36px;
  font-size: 13px;
}

.el-button--small {
  padding: 8px 16px;
  font-size: 13px;
  border-radius: 6px;
  font-weight: 500;
  transition: all 0.3s;
}

/* Ultra compact action buttons */
.el-button--mini {
  padding: 2px 6px;
  font-size: 10px;
  margin: 0 1px;
  border: none;
  background: transparent;
}

.el-button--text {
  padding: 2px 4px;
  font-size: 10px;
  font-weight: 500;
}

.el-button--text:hover {
  background-color: rgba(64, 158, 255, 0.1);
}

/* Ultra compact tags */
.el-tag--mini {
  font-size: 9px;
  padding: 1px 4px;
  margin: 1px;
  height: 18px;
  line-height: 16px;
}

/* Better spacing for ingredients */
.ingredients-cell .el-tag {
  margin: 1px;
}

/* Compact owner column */
.owner-info {
  line-height: 1.2;
}

.owner-info .owner-name {
  font-size: 11px;
  font-weight: 500;
}

.owner-info .owner-email {
  font-size: 9px;
  color: #909399;
}

/* Recipe view dialog styling */
.recipe-view-dialog .el-dialog__body {
  padding: 15px 20px;
}

.recipe-details {
  font-size: 13px;
}

.detail-item {
  margin-bottom: 15px;
}

.detail-item label {
  font-weight: 600;
  color: #333;
  display: block;
  margin-bottom: 5px;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.detail-item span, .detail-item p {
  color: #666;
  line-height: 1.4;
}

.detail-item p {
  margin: 0;
}

.ingredients-list {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
}

.ingredient-tag {
  margin: 2px;
}

.steps-list {
  margin: 0;
  padding-left: 20px;
}

.steps-list li {
  margin-bottom: 8px;
  line-height: 1.4;
}

.recipe-image-container {
  margin-top: 5px;
}

.recipe-detail-image {
  max-width: 300px;
  max-height: 200px;
  object-fit: cover;
  border-radius: 4px;
  border: 1px solid #e4e7ed;
  margin-right: 10px;
}

/* Ultra compact action buttons with icons only */
.el-button--text.el-button--mini {
  padding: 1px 2px;
  font-size: 10px;
  margin: 0;
  border-radius: 2px;
  min-width: 14px;
  height: 14px;
  line-height: 1;
}

.el-button--text.el-button--mini:hover {
  background-color: rgba(64, 158, 255, 0.1);
}

.el-button--text.el-button--mini i {
  font-size: 8px;
}

/* Make table even more compact */
.el-table--mini .el-table__cell {
  padding: 1px 0;
}

.el-table--mini td {
  padding: 0;
}

/* Owner info styling */
.owner-info .owner-name {
  font-size: 9px;
  font-weight: 500;
  line-height: 1;
}

.owner-info .owner-email {
  font-size: 7px;
  color: #909399;
  line-height: 1;
}

/* Professional card-like appearance for the view dialog */
.recipe-view-dialog .el-dialog {
  border-radius: 8px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.recipe-view-dialog .el-dialog__header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 8px 8px 0 0;
  padding: 15px 20px;
}

.recipe-view-dialog .el-dialog__title {
  color: white;
  font-weight: 600;
}
</style>
