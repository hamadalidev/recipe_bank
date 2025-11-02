import request from '@/utils/request'

export function getRecipes(params) {
  return request({
    url: '/recipes',
    method: 'get',
    params
  })
}

export function getRecipe(id) {
  return request({
    url: `/recipes/${id}`,
    method: 'get'
  })
}

export function createRecipe(data) {
  return request({
    url: '/recipes',
    method: 'post',
    data,
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  })
}

export function updateRecipe(id, data) {
  return request({
    url: `/recipes/${id}`,
    method: 'post', // Use POST with _method field for file uploads
    data,
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  })
}

export function deleteRecipe(id) {
  return request({
    url: `/recipes/${id}`,
    method: 'delete'
  })
}

export function getCuisineTypes() {
  return request({
    url: '/cuisine-types/dropdown',
    method: 'get'
  })
}
